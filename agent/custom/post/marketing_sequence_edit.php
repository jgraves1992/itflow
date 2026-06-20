<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['edit_marketing_sequence'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

$sequence_id   = intval($_POST['sequence_id'] ?? 0);
$sequence_name = sanitizeInput($_POST['sequence_name'] ?? '');
$sequence_desc = sanitizeInput($_POST['sequence_description'] ?? '');
$from_name     = sanitizeInput($_POST['sequence_from_name'] ?? '');
$from_email    = sanitizeInput($_POST['sequence_from_email'] ?? '');
$active        = intval($_POST['sequence_active'] ?? 1) ? 1 : 0;
$send_time     = preg_match('/^\d{2}:\d{2}$/', $_POST['sequence_send_time'] ?? '') ? $_POST['sequence_send_time'] . ':00' : '09:00:00';

if (!$sequence_id || !$sequence_name) {
    flash_alert('Sequence name is required.', 'error');
    header("Location: /agent/custom/marketing_sequence_details.php?id=$sequence_id");
    exit;
}

if ($from_email && !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
    flash_alert('Invalid from email address.', 'error');
    header("Location: /agent/custom/marketing_sequence_details.php?id=$sequence_id");
    exit;
}

$n  = mysqli_real_escape_string($mysqli, $sequence_name);
$d  = mysqli_real_escape_string($mysqli, $sequence_desc);
$fn = mysqli_real_escape_string($mysqli, $from_name);
$fe = mysqli_real_escape_string($mysqli, $from_email);

mysqli_query($mysqli,
    "UPDATE marketing_sequences
     SET sequence_name='$n', sequence_description='$d',
         sequence_from_name='$fn', sequence_from_email='$fe',
         sequence_active=$active, sequence_send_time='$send_time'
     WHERE sequence_id=$sequence_id");

flash_alert('Sequence updated.');
header("Location: /agent/custom/marketing_sequence_details.php?id=$sequence_id");
exit;
