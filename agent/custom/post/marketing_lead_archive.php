<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['archive_marketing_lead']) && !isset($_GET['archive_marketing_lead'])) return;

// Support both POST and GET (GET used for the archive button link)
$csrf  = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
$lead_id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

validateCSRFToken($csrf);
enforceUserPermission('module_client', 2);

if (!$lead_id) {
    header('Location: /agent/custom/marketing_leads.php');
    exit;
}

// Pause all active enrollments before archiving
mysqli_query($mysqli,
    "UPDATE marketing_enrollments
     SET enrollment_status = 'paused'
     WHERE enrollment_lead_id = $lead_id AND enrollment_status = 'active'");

mysqli_query($mysqli,
    "UPDATE marketing_leads SET lead_archived_at = NOW() WHERE lead_id = $lead_id");

flash_alert('Lead archived.');
header('Location: /agent/custom/marketing_leads.php');
exit;

