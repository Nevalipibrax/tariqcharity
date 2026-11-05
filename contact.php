<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/private/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok'=>false,'message'=>'Method not allowed'], 405);
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$name = trim((string)($input['name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$message = trim((string)($input['message'] ?? ''));
if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 10 || mb_strlen($message) > 5000) json_response(['ok'=>false,'message'=>'Please provide a name, valid email and message (10–5,000 characters).'], 422);
$s = fn($v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
try {
    send_tco_email('Website enquiry – TCO', '<h2>New website enquiry</h2><p><b>Name:</b> '.$s($name).'</p><p><b>Email:</b> '.$s($email).'</p><p><b>Message:</b><br>'.nl2br($s($message)).'</p>', $email);
    json_response(['ok'=>true,'message'=>'Thank you. Your message has been sent to TCO.']);
} catch (Throwable $e) {
    error_log('TCO contact email failed: ' . $e->getMessage());
    json_response(['ok'=>false,'message'=>'We could not send your message right now. Please try again later.'], 500);
}
