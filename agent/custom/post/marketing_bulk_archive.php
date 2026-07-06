<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['bulk_archive_marketing_leads'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

$lead_ids = array_map('intval', (array) ($_POST['lead_ids'] ?? []));
$lead_ids = array_filter($lead_ids);

if (empty($lead_ids)) {
    flash_alert('No leads selected.', 'error');
    header('Location: /agent/custom/marketing_leads.php');
    exit;
}

$ids_sql = implode(',', $lead_ids);

// Pause active enrollments for all selected leads
mysqli_query($mysqli,
    "UPDATE marketing_enrollments
     SET enrollment_status = 'paused'
     WHERE enrollment_lead_id IN ($ids_sql) AND enrollment_status = 'active'");

// Archive the leads
mysqli_query($mysqli,
    "UPDATE marketing_leads SET lead_archived_at = NOW()
     WHERE lead_id IN ($ids_sql) AND lead_archived_at IS NULL");

$count = mysqli_affected_rows($mysqli);
flash_alert("$count lead(s) archived.");
header('Location: /agent/custom/marketing_leads.php');
exit;
