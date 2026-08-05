<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['edit_marketing_step'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

$step_id      = intval($_POST['step_id'] ?? 0);
$sequence_id  = intval($_POST['sequence_id'] ?? 0);
$step_subject = escapeSql($_POST['step_subject'] ?? '');
$step_body    = $_POST['step_body'] ?? '';
$step_delay   = max(0, intval($_POST['step_delay_days'] ?? 0));

if (!$step_id || !$step_subject || !$step_body) {
    flashAlert('Subject and body are required.', 'error');
    header("Location: /agent/custom/marketing_sequence_details.php?id=$sequence_id");
    exit;
}

// $step_subject already escaped by escapeSql() — only $step_body needs it (it skips sanitizeInput to preserve TinyMCE HTML)
$body = mysqli_real_escape_string($mysqli, $step_body);

mysqli_query($mysqli,
    "UPDATE marketing_sequence_steps
     SET step_subject='$step_subject', step_body='$body', step_delay_days=$step_delay
     WHERE step_id=$step_id AND step_sequence_id=$sequence_id");

flashAlert('Step updated.');
header("Location: /agent/custom/marketing_sequence_details.php?id=$sequence_id");
exit;
