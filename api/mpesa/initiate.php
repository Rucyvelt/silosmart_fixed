<?php
// ============================================================
// SILOSMART - M-PESA STK PUSH INITIATOR
// POST /api/mpesa/initiate.php
// ============================================================
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_login('/login.php');
require_role(['tenant_admin', 'super_admin']);

$data    = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$phone   = clean($data['phone'] ?? '');
$plan_id = (int)($data['plan_id'] ?? 0);
$period  = clean($data['period'] ?? 'monthly'); // monthly | yearly
$org_id  = ss_get_current_user()['organisation_id'];

if (!$phone || !$plan_id) json_response(['error' => 'Phone and plan are required.'], 400);

// ─── Get Plan ─────────────────────────────────────────────────
$plan = db()->prepare("SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1");
$plan->execute([$plan_id]);
$plan = $plan->fetch();
if (!$plan) json_response(['error' => 'Invalid subscription plan.'], 404);

$amount = $period === 'yearly' ? $plan['price_yearly'] : $plan['price_monthly'];
$amount = (int)ceil($amount); // M-Pesa requires integer

// Format phone: must be 254XXXXXXXXX
$phone = preg_replace('/[^0-9]/', '', $phone);
if (substr($phone, 0, 1) === '0') $phone = '254' . substr($phone, 1);
if (substr($phone, 0, 3) !== '254') $phone = '254' . $phone;

// ─── Get M-Pesa Access Token ──────────────────────────────────
$consumer_key    = get_setting('mpesa_consumer_key', MPESA_CONSUMER_KEY);
$consumer_secret = get_setting('mpesa_consumer_secret', MPESA_CONSUMER_SECRET);
$env             = get_setting('mpesa_environment', MPESA_ENV);

$auth_url = $env === 'production'
    ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
    : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

$ch = curl_init($auth_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    CURLOPT_USERPWD        => "$consumer_key:$consumer_secret",
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$auth_response = json_decode(curl_exec($ch), true);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($auth_response['access_token'])) {
    error_log('M-Pesa auth failed: ' . json_encode($auth_response));
    // Demo mode: simulate success
    $invoice_num = 'INV-' . date('Ymd') . '-' . rand(1000,9999);
    json_response([
        'success'            => true,
        'demo_mode'          => true,
        'message'            => "Demo: STK Push simulated to $phone for KES $amount",
        'CheckoutRequestID'  => 'demo_' . uniqid(),
        'invoice'            => $invoice_num,
    ]);
}

$access_token = $auth_response['access_token'];

// ─── Build STK Push ───────────────────────────────────────────
$shortcode   = get_setting('mpesa_shortcode', MPESA_SHORTCODE);
$passkey     = get_setting('mpesa_passkey', MPESA_PASSKEY);
$timestamp   = date('YmdHis');
$password    = base64_encode($shortcode . $passkey . $timestamp);
$callback    = get_setting('mpesa_callback_url', MPESA_CALLBACK_URL);
$description = "SiloSmart {$plan['name']} Plan - $period subscription";
$invoice_num = 'INV-' . date('Ymd') . '-' . rand(1000,9999);

$stk_url = $env === 'production'
    ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
    : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

$stk_data = [
    'BusinessShortCode' => $shortcode,
    'Password'          => $password,
    'Timestamp'         => $timestamp,
    'TransactionType'   => 'CustomerPayBillOnline',
    'Amount'            => $amount,
    'PartyA'            => $phone,
    'PartyB'            => $shortcode,
    'PhoneNumber'       => $phone,
    'CallBackURL'       => $callback,
    'AccountReference'  => 'SILO-' . str_pad($org_id, 6, '0', STR_PAD_LEFT),
    'TransactionDesc'   => $description,
];

$ch = curl_init($stk_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($stk_data),
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$stk_response = json_decode(curl_exec($ch), true);
$http_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || ($stk_response['ResponseCode'] ?? '') !== '0') {
    error_log('STK Push failed: ' . json_encode($stk_response));
    json_response(['error' => 'Payment initiation failed. Please try again.'], 500);
}

// ─── Store pending payment ────────────────────────────────────
$checkout_id  = $stk_response['CheckoutRequestID'];
$merchant_id  = $stk_response['MerchantRequestID'];

db()->prepare("
    INSERT INTO payments (organisation_id, plan_id, amount, currency, gateway, mpesa_phone,
                          checkout_request_id, merchant_request_id, status, payment_type, billing_period, 
                          invoice_number, description)
    VALUES (?,?,?,'KES','mpesa',?,?,?,'pending','subscription',?,?,?)
")->execute([
    $org_id, $plan_id, $amount, $phone,
    $checkout_id, $merchant_id, $period, $invoice_num, $description
]);

log_activity('payment_initiated', 'payment', "STK Push sent to $phone for KES $amount ({$plan['name']} plan)", 'payment');

json_response([
    'success'           => true,
    'message'           => "STK Push sent to $phone. Enter your M-Pesa PIN to complete.",
    'CheckoutRequestID' => $checkout_id,
    'invoice'           => $invoice_num,
    'amount'            => $amount,
    'plan'              => $plan['name'],
]);
