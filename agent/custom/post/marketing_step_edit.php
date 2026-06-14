<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['edit_marketing_step'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

$step_id      = intval($_POST['step_id'] ?? 0);
$sequence_id  = intval($_POST['sequence_id'] ?? 0);
$step_subject = sanitizeInput($_POST['step_subject'] ?? '');
$step_body    = $_POST['step_body'] ?? '';
$step_delay   = max(0, intval($_POST['step_delay_days'] ?? 0));

if (!$step_id || !$step_subject || !$step_body) {
    $_SESSION['error'] = 'Subject and body are required.';
    header("Location: /agent/custom/marketing_sequence_details.php?id=$sequence_id");
    exit;
}

$subj = mysqli_real_escape_string($mysqli, $step_subject);
$body = mysqli_real_escape_string($mysqli, $step_body);

mysqli_query($mysqli,
    "UPDATE marketing_sequence_steps
     SET step_subject='$subj', step_body='$body', step_delay_days=$step_delay
     WHERE step_id=$step_id AND step_sequence_id=$sequence_id");

$_SESSION['success'] = 'Step updated.';
header("Location: /agent/custom/marketing_sequence_details.php?id=$sequence_id");
exit;
