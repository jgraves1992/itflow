<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['bulk_enroll_marketing_leads'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

$lead_ids    = isset($_POST['lead_ids']) && is_array($_POST['lead_ids']) ? array_map('intval', $_POST['lead_ids']) : [];
$lead_ids    = array_filter(array_unique($lead_ids));
$sequence_id = intval($_POST['sequence_id'] ?? 0);

if (empty($lead_ids) || !$sequence_id) {
    flash_alert('Select at least one lead and a sequence.', 'error');
    header('Location: /agent/custom/marketing_leads.php');
    exit;
}

// Verify sequence exists and is active
$sequence = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT * FROM marketing_sequences WHERE sequence_id = $sequence_id AND sequence_active = 1 AND sequence_archived_at IS NULL"));

if (!$sequence) {
    flash_alert('Sequence not found or inactive.', 'error');
    header('Location: /agent/custom/marketing_leads.php');
    exit;
}

// Find first step
$first_step = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT * FROM marketing_sequence_steps
     WHERE step_sequence_id = $sequence_id ORDER BY step_order ASC LIMIT 1"));

if (!$first_step) {
    flash_alert('Sequence has no steps. Add at least one email step first.', 'error');
    header('Location: /agent/custom/marketing_leads.php');
    exit;
}

$first_step_id = intval($first_step['step_id']);
$delay_days    = intval($first_step['step_delay_days']);
$send_time     = $sequence['sequence_send_time'] ?? '09:00:00';

$send_date    = date('Y-m-d', strtotime("+$delay_days days"));
$next_send_at = $send_date . ' ' . $send_time;

$enrolled_count = 0;
$skipped_count  = 0;

foreach ($lead_ids as $lead_id) {

    $lead = mysqli_fetch_assoc(mysqli_query($mysqli,
        "SELECT lead_id, lead_unsubscribed FROM marketing_leads WHERE lead_id = $lead_id AND lead_archived_at IS NULL"));

    if (!$lead || $lead['lead_unsubscribed']) {
        $skipped_count++;
        continue;
    }

    $existing = mysqli_fetch_assoc(mysqli_query($mysqli,
        "SELECT enrollment_id FROM marketing_enrollments
         WHERE enrollment_lead_id = $lead_id AND enrollment_sequence_id = $sequence_id"));

    if ($existing) {
        $skipped_count++;
        continue;
    }

    mysqli_query($mysqli,
        "INSERT INTO marketing_enrollments
            (enrollment_lead_id, enrollment_sequence_id, enrollment_next_step_id, enrollment_next_send_at)
         VALUES ($lead_id, $sequence_id, $first_step_id, '$next_send_at')");

    $enrolled_count++;
}

$sequence_name = nullable_htmlentities($sequence['sequence_name']);
$message = "$enrolled_count lead(s) enrolled in <strong>$sequence_name</strong>.";
if ($skipped_count > 0) {
    $message .= " $skipped_count skipped (already enrolled or unsubscribed).";
}

flash_alert($message);
header('Location: /agent/custom/marketing_leads.php');
exit;
