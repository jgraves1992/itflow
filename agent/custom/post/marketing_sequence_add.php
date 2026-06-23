<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['add_marketing_sequence'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

$sequence_name  = sanitizeInput($_POST['sequence_name'] ?? '');
$sequence_desc  = sanitizeInput($_POST['sequence_description'] ?? '');
$from_name      = sanitizeInput($_POST['sequence_from_name'] ?? '');
$from_email     = sanitizeInput($_POST['sequence_from_email'] ?? '');
$send_time      = preg_match('/^\d{2}:\d{2}$/', $_POST['sequence_send_time'] ?? '') ? $_POST['sequence_send_time'] . ':00' : '09:00:00';

if (!$sequence_name) {
    flash_alert('Sequence name is required.', 'error');
    header('Location: ../marketing_sequences.php');
    exit;
}

if ($from_email && !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
    flash_alert('Invalid from email address.', 'error');
    header('Location: ../marketing_sequences.php');
    exit;
}

// sanitizeInput() already escapes for SQL — do not re-escape, or quotes/backslashes get double-escaped into the stored value
mysqli_query($mysqli,
    "INSERT INTO marketing_sequences (sequence_name, sequence_description, sequence_from_name, sequence_from_email, sequence_send_time)
     VALUES ('$sequence_name', '$sequence_desc', '$from_name', '$from_email', '$send_time')");

$new_id = mysqli_insert_id($mysqli);

flash_alert("Sequence <strong>$sequence_name</strong> created. Add your first email step below.");
header("Location: /agent/custom/marketing_sequence_details.php?id=$new_id");
exit;
