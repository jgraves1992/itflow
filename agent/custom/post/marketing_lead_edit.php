<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['edit_marketing_lead'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

$lead_id   = intval($_POST['lead_id'] ?? 0);
$lead_name = sanitizeInput($_POST['lead_name'] ?? '');
$lead_email = sanitizeInput($_POST['lead_email'] ?? '');

if (!$lead_id || !$lead_name || !$lead_email) {
    flash_alert('Missing required fields.', 'error');
    header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
    exit;
}

if (!filter_var($lead_email, FILTER_VALIDATE_EMAIL)) {
    flash_alert('Invalid email address.', 'error');
    header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
    exit;
}

$valid_statuses = ['new', 'contacted', 'qualified', 'converted', 'lost'];
$lead_status  = in_array($_POST['lead_status'] ?? '', $valid_statuses) ? $_POST['lead_status'] : 'new';
$lead_company = sanitizeInput($_POST['lead_company'] ?? '');
$lead_phone   = sanitizeInput($_POST['lead_phone'] ?? '');
$lead_source  = sanitizeInput($_POST['lead_source'] ?? '');
$lead_notes   = sanitizeInput($_POST['lead_notes'] ?? '');

// sanitizeInput() already escapes for SQL — do not re-escape, or quotes/backslashes get double-escaped into the stored value
mysqli_query($mysqli,
    "UPDATE marketing_leads
     SET lead_name='$lead_name', lead_email='$lead_email', lead_company='$lead_company', lead_phone='$lead_phone',
         lead_source='$lead_source', lead_notes='$lead_notes', lead_status='$lead_status'
     WHERE lead_id=$lead_id");

flash_alert('Lead updated.');
header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
exit;
