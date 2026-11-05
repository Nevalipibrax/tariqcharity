<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/private/bootstrap.php';
$raw = file_get_contents('php://input');
$signature = $_SERVER['HTTP_FLUTTERWAVE_SIGNATURE'] ?? '';
$hash = (string)($config['flutterwave_secret_hash'] ?? '');
if ($hash === '' || $signature === '') { http_response_code(401); exit('Unauthorized'); }
$expected = base64_encode(hash_hmac('sha256', $raw, $hash, true));
if (!hash_equals($expected, $signature)) { http_response_code(401); exit('Invalid signature'); }
$payload = json_decode($raw, true) ?: [];
$data = $payload['data'] ?? [];
if (($payload['type'] ?? '') === 'charge.completed' && ($data['status'] ?? '') === 'succeeded' && !empty($data['id'])) {
    try { finalize_verified_payment((int)$data['id']); }
    catch (Throwable $e) { error_log('TCO webhook verification failed: ' . $e->getMessage()); http_response_code(500); exit('Verification failed'); }
}
http_response_code(200);
echo 'OK';
