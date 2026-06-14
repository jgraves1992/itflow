<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['add_marketing_step'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

$sequence_id  = intval($_POST['sequence_id'] ?? 0);
$step_subject = sanitizeInput($_POST['step_subject'] ?? '');
$step_body    = $_POST['step_body'] ?? ''; // Allow HTML from TinyMCE
$step_delay   = max(0, intval($_POST['step_delay_days'] ?? 0));

if (!$sequence_id || !$step_subject || !$step_body) {
    $_SESSION['error'] = 'Subject and body are required.';
    header("Location: /agent/custom/marketing_sequence_details.php?id=$sequence_id");
    exit;
}

// Determine next step order
$order_result = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT COALESCE(MAX(step_order), 0) + 1 AS next_order FROM marketing_sequence_steps WHERE step_sequence_id = $sequence_id"));
$step_order = intval($order_result['next_order']);

$subj = mysqli_real_escape_string($mysqli, $step_subject);
$body = mysqli_real_escape_string($mysqli, $step_body);

mysqli_query($mysqli,
    "INSERT INTO marketing_sequence_steps (step_sequence_id, step_order, step_delay_days, step_subject, step_body)
     VALUES ($sequence_id, $step_order, $step_delay, '$subj', '$body')");

$_SESSION['success'] = "Step $step_order added.";
header("Location: /agent/custom/marketing_sequence_details.php?id=$sequence_id");
exit;
