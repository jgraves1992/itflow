<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['archive_marketing_sequence'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

$sequence_id = intval($_POST['sequence_id'] ?? 0);

if (!$sequence_id) {
    header('Location: /agent/custom/marketing_sequences.php');
    exit;
}

// Pause active enrollments
mysqli_query($mysqli,
    "UPDATE marketing_enrollments
     SET enrollment_status = 'paused'
     WHERE enrollment_sequence_id = $sequence_id AND enrollment_status = 'active'");

mysqli_query($mysqli,
    "UPDATE marketing_sequences SET sequence_archived_at = NOW() WHERE sequence_id = $sequence_id");

flash_alert('Sequence archived.');
header('Location: /agent/custom/marketing_sequences.php');
exit;
