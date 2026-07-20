<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'Method not allowed']); exit; }
require_once dirname(__DIR__,2).'/includes/functions.php';
ss_session_start();
if (!is_logged_in()) { http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }

$data = json_decode(file_get_contents('php://input'),true) ?: [];
$phone  = preg_replace('/\D/','',$data['phone'] ?? '254700000000');
$amount = (int)($data['amount'] ?? 1);

try {
    $env      = get_setting('mpesa_environment','sandbox');
    $key      = get_setting('mpesa_consumer_key','');
    $secret   = get_setting('mpesa_consumer_secret','');
    $shortcode= get_setting('mpesa_shortcode','174379');
    $passkey  = get_setting('mpesa_passkey','');

    if (!$key || !$secret) {
        echo json_encode(['error'=>'M-Pesa credentials not configured. Fill in Payment settings first.']); exit;
    }

    $base_url = $env==='production' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';

    // Get token
    $ch = curl_init("$base_url/oauth/v1/generate?grant_type=client_credentials");
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Basic '.base64_encode("$key:$secret")]]);
    $res = json_decode(curl_exec($ch),true);
    curl_close($ch);

    if (empty($res['access_token'])) { echo json_encode(['error'=>'Could not get M-Pesa token — check credentials.']); exit; }

    $token     = $res['access_token'];
    $timestamp = date('YmdHis');
    $password  = base64_encode($shortcode.$passkey.$timestamp);
    $callback  = get_setting('mpesa_callback_url','https://silo.free.nf/api/mpesa/callback.php');

    $payload = [
        'BusinessShortCode'=>$shortcode,'Password'=>$password,'Timestamp'=>$timestamp,
        'TransactionType'=>'CustomerPayBillOnline','Amount'=>$amount,
        'PartyA'=>$phone,'PartyB'=>$shortcode,'PhoneNumber'=>$phone,
        'CallBackURL'=>$callback,'AccountReference'=>'SiloSmart Test',
        'TransactionDesc'=>'STK Test Push',
    ];

    $ch = curl_init("$base_url/mpesa/stkpush/v1/processrequest");
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>json_encode($payload),
        CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Content-Type: application/json'],
    ]);
    $resp = json_decode(curl_exec($ch),true);
    curl_close($ch);

    if (isset($resp['ResponseCode']) && $resp['ResponseCode']==='0') {
        echo json_encode(['success'=>true,'message'=>'STK Push sent! Check the phone '.$phone]);
    } else {
        echo json_encode(['error'=>$resp['errorMessage']??$resp['ResponseDescription']??'STK Push failed.']);
    }
} catch(Exception $e) {
    echo json_encode(['error'=>'DB not available: '.$e->getMessage()]);
}
