<?php
// ============================================================
// SILOSMART - M-PESA CALLBACK HANDLER
// POST /api/mpesa/callback.php
// ============================================================
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Only accept POST from Safaricom IPs
$allowed_ips = ['196.201.214.200','196.201.214.206','196.201.213.114','196.201.214.207',
                 '196.201.214.208','196.201.213.44','196.201.214.171','196.201.213.130',
                 '196.201.212.127','196.201.212.138','196.201.214.137','196.201.214.138'];
$client_ip = get_client_ip();
// In production: enforce IP whitelist
// if (!in_array($client_ip, $allowed_ips)) { http_response_code(403); exit; }

$raw    = file_get_contents('php://input');
$data   = json_decode($raw, true);
error_log('M-Pesa Callback: ' . $raw);

if (!$data) { echo json_encode(['ResultCode'=>1,'ResultDesc'=>'Invalid data']); exit; }

$body         = $data['Body']['stkCallback'] ?? [];
$result_code  = $body['ResultCode'] ?? 1;
$result_desc  = $body['ResultDesc'] ?? '';
$checkout_id  = $body['CheckoutRequestID'] ?? '';

// ─── Find Payment ─────────────────────────────────────────────
$stmt = db()->prepare("SELECT * FROM payments WHERE checkout_request_id = ? AND status = 'pending' LIMIT 1");
$stmt->execute([$checkout_id]);
$payment = $stmt->fetch();

if (!$payment) {
    error_log('M-Pesa callback: payment not found for ' . $checkout_id);
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    exit;
}

if ($result_code === 0) {
    // ─── SUCCESS ──────────────────────────────────────────────
    $items = [];
    foreach (($body['CallbackMetadata']['Item'] ?? []) as $item) {
        $items[$item['Name']] = $item['Value'] ?? null;
    }

    $mpesa_receipt  = $items['MpesaReceiptNumber'] ?? uniqid('MPESA');
    $transaction_at = $items['TransactionDate'] ?? date('YmdHis');
    $amount_paid    = $items['Amount'] ?? $payment['amount'];

    db()->prepare("
        UPDATE payments SET status = 'completed', gateway_ref = ?, paid_at = ?, metadata = ? WHERE id = ?
    ")->execute([
        $mpesa_receipt,
        date('Y-m-d H:i:s', strtotime($transaction_at)),
        json_encode($items),
        $payment['id']
    ]);

    // ─── Activate subscription ────────────────────────────────
    $plan = db()->prepare("SELECT * FROM subscription_plans WHERE id = ?")->execute([$payment['plan_id']])
            ? db()->query("SELECT * FROM subscription_plans WHERE id = {$payment['plan_id']}")->fetch()
            : null;

    if ($plan && $payment['organisation_id']) {
        $expires = $payment['billing_period'] === 'yearly'
            ? date('Y-m-d H:i:s', strtotime('+1 year'))
            : date('Y-m-d H:i:s', strtotime('+1 month'));

        db()->prepare("
            UPDATE organisations SET 
                plan_id = ?, plan_expires_at = ?, status = 'active',
                max_silos = ?, max_users = ?,
                features = ?
            WHERE id = ?
        ")->execute([
            $plan['id'], $expires,
            $plan['max_silos'], $plan['max_users'],
            $plan['features'],
            $payment['organisation_id']
        ]);

        // Notify admin
        $admins = db()->prepare("SELECT id FROM users WHERE organisation_id = ? AND role = 'tenant_admin'");
        $admins->execute([$payment['organisation_id']]);
        foreach ($admins->fetchAll() as $admin) {
            create_notification(
                $admin['id'], 'payment',
                'Payment Confirmed! ✅',
                "Your {$plan['name']} subscription is now active. Receipt: $mpesa_receipt",
                '/billing.php',
                $payment['organisation_id']
            );
        }
    }

    error_log("M-Pesa payment confirmed: $mpesa_receipt for org {$payment['organisation_id']}");

} else {
    // ─── FAILED ───────────────────────────────────────────────
    db()->prepare("UPDATE payments SET status = 'failed', metadata = ? WHERE id = ?")
        ->execute([json_encode(['error' => $result_desc]), $payment['id']]);

    // Notify admin of failed payment
    $admins = db()->prepare("SELECT id FROM users WHERE organisation_id = ? AND role = 'tenant_admin'");
    $admins->execute([$payment['organisation_id']]);
    foreach ($admins->fetchAll() as $admin) {
        create_notification(
            $admin['id'], 'payment',
            'Payment Failed ❌',
            "M-Pesa payment of KES {$payment['amount']} failed: $result_desc",
            '/billing.php',
            $payment['organisation_id']
        );
    }
    error_log("M-Pesa payment FAILED for org {$payment['organisation_id']}: $result_desc");
}

echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
