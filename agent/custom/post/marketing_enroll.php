<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['enroll_marketing_lead'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

$lead_id     = intval($_POST['lead_id'] ?? 0);
$sequence_id = intval($_POST['sequence_id'] ?? 0);

if (!$lead_id || !$sequence_id) {
    $_SESSION['error'] = 'Invalid lead or sequence.';
    header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
    exit;
}

// Verify lead exists and is not unsubscribed
$lead = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT * FROM marketing_leads WHERE lead_id = $lead_id AND lead_archived_at IS NULL"));

if (!$lead) {
    $_SESSION['error'] = 'Lead not found.';
    header("Location: /agent/custom/marketing_leads.php");
    exit;
}

if ($lead['lead_unsubscribed']) {
    $_SESSION['error'] = 'Cannot enroll an unsubscribed lead.';
    header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
    exit;
}

// Verify sequence exists and is active
$sequence = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT * FROM marketing_sequences WHERE sequence_id = $sequence_id AND sequence_active = 1 AND sequence_archived_at IS NULL"));

if (!$sequence) {
    $_SESSION['error'] = 'Sequence not found or inactive.';
    header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
    exit;
}

// Check for duplicate enrollment
$existing = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT enrollment_id FROM marketing_enrollments
     WHERE enrollment_lead_id = $lead_id AND enrollment_sequence_id = $sequence_id"));

if ($existing) {
    $_SESSION['error'] = 'Lead is already enrolled in this sequence.';
    header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
    exit;
}

// Find first step
$first_step = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT * FROM marketing_sequence_steps
     WHERE step_sequence_id = $sequence_id ORDER BY step_order ASC LIMIT 1"));

if (!$first_step) {
    $_SESSION['error'] = 'Sequence has no steps. Add at least one email step first.';
    header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
    exit;
}

$first_step_id = intval($first_step['step_id']);
$delay_days    = intval($first_step['step_delay_days']);
$send_time     = $sequence['sequence_send_time'] ?? '09:00:00';

$send_date    = date('Y-m-d', strtotime("+$delay_days days"));
$next_send_at = $send_date . ' ' . $send_time;

mysqli_query($mysqli,
    "INSERT INTO marketing_enrollments
        (enrollment_lead_id, enrollment_sequence_id, enrollment_next_step_id, enrollment_next_send_at)
     VALUES ($lead_id, $sequence_id, $first_step_id, '$next_send_at')");

$_SESSION['success'] = 'Lead enrolled. First email will send on <strong>' . date('M j, Y g:i A', strtotime($next_send_at)) . '</strong>.';
header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
exit;
