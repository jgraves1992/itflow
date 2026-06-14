<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['edit_marketing_lead'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

$lead_id   = intval($_POST['lead_id'] ?? 0);
$lead_name = sanitizeInput($_POST['lead_name'] ?? '');
$lead_email = sanitizeInput($_POST['lead_email'] ?? '');

if (!$lead_id || !$lead_name || !$lead_email) {
    $_SESSION['error'] = 'Missing required fields.';
    header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
    exit;
}

if (!filter_var($lead_email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Invalid email address.';
    header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
    exit;
}

$valid_statuses = ['new', 'contacted', 'qualified', 'converted', 'lost'];
$lead_status  = in_array($_POST['lead_status'] ?? '', $valid_statuses) ? $_POST['lead_status'] : 'new';
$lead_company = sanitizeInput($_POST['lead_company'] ?? '');
$lead_phone   = sanitizeInput($_POST['lead_phone'] ?? '');
$lead_source  = sanitizeInput($_POST['lead_source'] ?? '');
$lead_notes   = sanitizeInput($_POST['lead_notes'] ?? '');

$n  = mysqli_real_escape_string($mysqli, $lead_name);
$e  = mysqli_real_escape_string($mysqli, $lead_email);
$c  = mysqli_real_escape_string($mysqli, $lead_company);
$p  = mysqli_real_escape_string($mysqli, $lead_phone);
$so = mysqli_real_escape_string($mysqli, $lead_source);
$no = mysqli_real_escape_string($mysqli, $lead_notes);
$st = mysqli_real_escape_string($mysqli, $lead_status);

mysqli_query($mysqli,
    "UPDATE marketing_leads
     SET lead_name='$n', lead_email='$e', lead_company='$c', lead_phone='$p',
         lead_source='$so', lead_notes='$no', lead_status='$st'
     WHERE lead_id=$lead_id");

$_SESSION['success'] = 'Lead updated.';
header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
exit;
