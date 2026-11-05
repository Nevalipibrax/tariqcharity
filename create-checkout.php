<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/private/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok'=>false,'message'=>'Method not allowed'], 405);
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$amount = filter_var($input['amount'] ?? null, FILTER_VALIDATE_INT);
$email = trim((string)($input['email'] ?? ''));
$name = trim((string)($input['name'] ?? ''));
$programs = ['Where it is needed most','Water and sanitation','Education and school construction','Orphan and child welfare','Food and humanitarian assistance','Mosque construction','Health and medical assistance','Youth and women empowerment','Widow and vulnerable family support','Qurban and Udhiya'];
$program = (string)($input['program'] ?? 'Where it is needed most');
$frequency = (string)($input['frequency'] ?? 'Once');
if (!$amount || $amount < 1000 || $amount > 100000000 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($name) > 120 || !in_array($program, $programs, true) || !in_array($frequency, ['Once'], true)) {
    json_response(['ok'=>false,'message'=>'Enter a valid donation of UGX 1,000 or more and a valid email. Monthly giving will be enabled after recurring billing is configured.'], 422);
}
$txRef = 'TCO-' . bin2hex(random_bytes(12));
$siteUrl = rtrim((string)$config['site_url'], '/');
if (!filter_var($siteUrl, FILTER_VALIDATE_URL) || str_contains($siteUrl, 'YOUR-DOMAIN')) json_response(['ok'=>false,'message'=>'Website URL is not configured by the administrator yet.'], 503);
try {
    $stmt = db()->prepare('INSERT INTO donations (tx_ref, amount, currency, donor_name, donor_email, program, frequency) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$txRef, $amount, 'UGX', $name, $email, $program, $frequency]);
    $response = flw_request('POST', '/payments', [
        'tx_ref' => $txRef,
        'amount' => (string)$amount,
        'currency' => 'UGX',
        'redirect_url' => $siteUrl . '/payment-return.php',
        'customer' => ['email'=>$email, 'name'=>$name ?: 'TCO Supporter'],
        'customizations' => ['title'=>'Tariq Charity Organization', 'description'=>'Donation to '.$program, 'logo'=>''],
        'payment_options' => 'card, mobilemoneyuganda',
        'meta' => ['organization'=>'Tariq Charity Organization','program'=>$program,'frequency'=>'Once']
    ]);
    $link = $response['data']['link'] ?? '';
    if (!$link || !filter_var($link, FILTER_VALIDATE_URL) || !str_starts_with($link, 'https://')) throw new RuntimeException('No secure checkout link was returned.');
    json_response(['ok'=>true,'checkout_url'=>$link]);
} catch (Throwable $e) {
    error_log('TCO checkout error: ' . $e->getMessage());
    json_response(['ok'=>false,'message'=>'We could not start checkout. Please try again later.'], 502);
}
