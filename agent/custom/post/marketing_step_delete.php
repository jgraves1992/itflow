<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['delete_marketing_step'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

$step_id     = intval($_POST['step_id'] ?? 0);
$sequence_id = intval($_POST['sequence_id'] ?? 0);

if (!$step_id || !$sequence_id) {
    header("Location: /agent/custom/marketing_sequence_details.php?id=$sequence_id");
    exit;
}

// Stall any enrollments waiting on this step
mysqli_query($mysqli,
    "UPDATE marketing_enrollments
     SET enrollment_status = 'paused'
     WHERE enrollment_next_step_id = $step_id AND enrollment_status = 'active'");

mysqli_query($mysqli,
    "DELETE FROM marketing_sequence_steps WHERE step_id = $step_id AND step_sequence_id = $sequence_id");

// Re-number remaining steps to keep order sequential
$steps = mysqli_query($mysqli,
    "SELECT step_id FROM marketing_sequence_steps WHERE step_sequence_id = $sequence_id ORDER BY step_order ASC");
$order = 1;
while ($s = mysqli_fetch_assoc($steps)) {
    mysqli_query($mysqli,
        "UPDATE marketing_sequence_steps SET step_order = $order WHERE step_id = {$s['step_id']}");
    $order++;
}

flash_alert('Step deleted.');
header("Location: /agent/custom/marketing_sequence_details.php?id=$sequence_id");
exit;
