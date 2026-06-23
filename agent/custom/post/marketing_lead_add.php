<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['add_marketing_lead'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client');

$lead_name    = sanitizeInput($_POST['lead_name'] ?? '');
$lead_email   = sanitizeInput($_POST['lead_email'] ?? '');
$lead_company = sanitizeInput($_POST['lead_company'] ?? '');
$lead_phone   = sanitizeInput($_POST['lead_phone'] ?? '');
$lead_source  = sanitizeInput($_POST['lead_source'] ?? '');
$lead_notes   = sanitizeInput($_POST['lead_notes'] ?? '');

$valid_statuses = ['new', 'contacted', 'qualified', 'converted', 'lost'];
$lead_status = in_array($_POST['lead_status'] ?? '', $valid_statuses) ? $_POST['lead_status'] : 'new';

if (!$lead_name || !$lead_email) {
    flash_alert('Name and email are required.', 'error');
    header('Location: /agent/custom/marketing_leads.php');
    exit;
}

if (!filter_var($lead_email, FILTER_VALIDATE_EMAIL)) {
    flash_alert('Invalid email address.', 'error');
    header('Location: /agent/custom/marketing_leads.php');
    exit;
}

$existing = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT lead_id FROM marketing_leads WHERE lead_email = '$lead_email' AND lead_archived_at IS NULL"));

if ($existing) {
    flash_alert('A lead with that email already exists.', 'error');
    header('Location: /agent/custom/marketing_leads.php');
    exit;
}

// sanitizeInput() already escapes for SQL — do not re-escape, or quotes/backslashes get double-escaped into the stored value
$token = mysqli_real_escape_string($mysqli, bin2hex(random_bytes(32)));

mysqli_query($mysqli,
    "INSERT INTO marketing_leads
        (lead_name, lead_email, lead_company, lead_phone, lead_source, lead_notes, lead_status, lead_unsubscribe_token)
     VALUES ('$lead_name', '$lead_email', '$lead_company', '$lead_phone', '$lead_source', '$lead_notes', '$lead_status', '$token')");

$new_id = mysqli_insert_id($mysqli);

// Also create a corresponding ITFlow client record (client_lead = 1)
try {
    $company_row     = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT company_currency FROM companies WHERE company_id = 1 LIMIT 1"));
    $currency_code   = mysqli_real_escape_string($mysqli, $company_row['company_currency'] ?? 'USD');
    $client_name_val = $lead_company ?: $lead_name;

    mysqli_query($mysqli,
        "INSERT INTO clients SET
            client_name = '$client_name_val',
            client_lead = 1,
            client_currency_code = '$currency_code',
            client_net_terms = 0,
            client_accessed_at = NOW()");
    $new_client_id = mysqli_insert_id($mysqli);

    if ($new_client_id) {
        mysqli_query($mysqli,
            "INSERT INTO contacts SET
                contact_name = '$lead_name',
                contact_email = '$lead_email',
                contact_phone = '$lead_phone',
                contact_primary = 1,
                contact_important = 1,
                contact_client_id = $new_client_id");

        mysqli_query($mysqli,
            "UPDATE marketing_leads SET lead_client_id = $new_client_id WHERE lead_id = $new_id");
    }
} catch (Exception $e) {
    // ITFlow client creation failed — marketing lead was still saved
}

flash_alert("Lead <strong>$lead_name</strong> added successfully.");
header("Location: /agent/custom/marketing_lead_details.php?id=$new_id");
exit;
