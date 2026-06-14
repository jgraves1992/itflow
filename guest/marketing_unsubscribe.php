<?php
/**
 * ITFlow Marketing Module - One-click unsubscribe
 * Public endpoint — no login required.
 * URL: /guest/marketing_unsubscribe.php?token=XXXX
 */

$base_path = __DIR__ . '/../';
require_once $base_path . 'config.php';
require_once $base_path . 'functions.php';

$token = trim($_GET['token'] ?? '');

$lead = null;
$already_unsubscribed = false;
$success = false;
$error   = false;

if ($token) {
    $t = mysqli_real_escape_string($mysqli, $token);
    $lead = mysqli_fetch_assoc(mysqli_query($mysqli,
        "SELECT lead_id, lead_name, lead_email, lead_unsubscribed
         FROM marketing_leads
         WHERE lead_unsubscribe_token = '$t' AND lead_archived_at IS NULL"));
}

if (!$lead) {
    $error = true;
} elseif ($lead['lead_unsubscribed']) {
    $already_unsubscribed = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_unsubscribe'])) {
    $lead_id = intval($lead['lead_id']);

    mysqli_query($mysqli,
        "UPDATE marketing_leads
         SET lead_unsubscribed = 1, lead_unsubscribed_at = NOW()
         WHERE lead_id = $lead_id");

    // Pause all active enrollments for this lead
    mysqli_query($mysqli,
        "UPDATE marketing_enrollments
         SET enrollment_status = 'unsubscribed'
         WHERE enrollment_lead_id = $lead_id AND enrollment_status = 'active'");

    $success = true;
}

// Read company name for the page
$company_name_row = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT company_name FROM companies WHERE company_id = 1 LIMIT 1"));
$company_name = $company_name_row['company_name'] ?? 'Our Company';

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribe — <?= htmlspecialchars($company_name) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
               background: #f4f6f9; display: flex; align-items: center;
               justify-content: center; min-height: 100vh; padding: 20px; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,.1);
                max-width: 480px; width: 100%; padding: 40px; text-align: center; }
        .icon { font-size: 48px; margin-bottom: 16px; }
        h1 { font-size: 22px; margin-bottom: 12px; color: #333; }
        p  { color: #666; line-height: 1.6; margin-bottom: 20px; }
        .email { background: #f8f9fa; padding: 8px 16px; border-radius: 4px;
                 display: inline-block; margin: 8px 0 20px; font-weight: 600; color: #333; }
        .btn { display: inline-block; padding: 12px 28px; border-radius: 5px;
               font-size: 15px; font-weight: 600; cursor: pointer; border: none;
               text-decoration: none; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .success-icon { color: #28a745; }
        .error-icon  { color: #dc3545; }
        .info-icon   { color: #17a2b8; }
        small { color: #aaa; font-size: 12px; display: block; margin-top: 20px; }
    </style>
</head>
<body>
<div class="card">

    <?php if ($error): ?>
        <div class="icon error-icon">✗</div>
        <h1>Invalid Link</h1>
        <p>This unsubscribe link is invalid or has expired. If you want to stop receiving emails, please reply directly and we'll remove you.</p>

    <?php elseif ($already_unsubscribed): ?>
        <div class="icon info-icon">ℹ</div>
        <h1>Already Unsubscribed</h1>
        <p>The address below is already unsubscribed and will not receive further marketing emails from us.</p>
        <div class="email"><?= htmlspecialchars($lead['lead_email']) ?></div>

    <?php elseif ($success): ?>
        <div class="icon success-icon">✓</div>
        <h1>You're Unsubscribed</h1>
        <p>We've removed the address below from all marketing emails. You won't hear from us again.</p>
        <div class="email"><?= htmlspecialchars($lead['lead_email']) ?></div>
        <p style="font-size:14px">Changed your mind? Just reply to any previous email and we'll re-add you.</p>

    <?php else: ?>
        <div class="icon">📧</div>
        <h1>Unsubscribe from <?= htmlspecialchars($company_name) ?></h1>
        <p>You'll be removed from all marketing emails. Transactional emails (invoices, tickets, etc.) are unaffected.</p>
        <div class="email"><?= htmlspecialchars($lead['lead_email']) ?></div>
        <form method="POST">
            <input type="hidden" name="confirm_unsubscribe" value="1">
            <button type="submit" class="btn btn-danger">Yes, Unsubscribe Me</button>
        </form>
        <small>Not you? Just close this page and ignore it.</small>

    <?php endif; ?>

</div>
</body>
</html>
