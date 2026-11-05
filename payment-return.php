<?php
declare(strict_types=1);
require_once dirname(__DIR__, 1) . '/private/bootstrap.php';
$status = (string)($_GET['status'] ?? 'unknown');
$transactionId = filter_var($_GET['transaction_id'] ?? null, FILTER_VALIDATE_INT);
$verified = false;
if ($status === 'successful' && $transactionId) {
    try { $verified = finalize_verified_payment((int)$transactionId); }
    catch (Throwable $e) { error_log('TCO return verification failed: ' . $e->getMessage()); }
}
$title = $verified ? 'Thank you for your generosity' : 'Payment not yet confirmed';
$message = $verified ? 'Your donation has been verified. Thank you for supporting Tariq Charity Organization.' : 'Your payment has not been confirmed yet. If you completed payment, please allow a little time for confirmation or contact TCO with your transaction reference.';
?><!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= htmlspecialchars($title) ?> | TCO</title><style>body{font:16px system-ui;background:#f7f6ef;color:#10251f;display:grid;min-height:100vh;place-items:center;margin:0}.card{background:white;padding:42px;border-radius:20px;max-width:580px;margin:20px;box-shadow:0 20px 60px #10251f18}a{display:inline-block;margin-top:20px;background:#10251f;color:white;padding:13px 20px;border-radius:30px;text-decoration:none}</style><main class="card"><h1><?= htmlspecialchars($title) ?></h1><p><?= htmlspecialchars($message) ?></p><a href="/">Return to TCO website</a></main></html>
