<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['unenroll_marketing_lead'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

$enrollment_id = intval($_POST['enrollment_id'] ?? 0);
$lead_id       = intval($_POST['lead_id'] ?? 0);

if (!$enrollment_id) {
    header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
    exit;
}

mysqli_query($mysqli,
    "DELETE FROM marketing_enrollments WHERE enrollment_id = $enrollment_id");

$_SESSION['success'] = 'Lead removed from sequence.';
header("Location: /agent/custom/marketing_lead_details.php?id=$lead_id");
exit;
