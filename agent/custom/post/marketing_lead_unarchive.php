<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['unarchive_marketing_lead']) && !isset($_GET['unarchive_marketing_lead'])) return;

$csrf    = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
$lead_id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

validateCSRFToken($csrf);
enforceUserPermission('module_client', 2);

if (!$lead_id) {
    header('Location: /agent/custom/marketing_leads.php?archived=1');
    exit;
}

mysqli_query($mysqli,
    "UPDATE marketing_leads SET lead_archived_at = NULL WHERE lead_id = $lead_id");

flash_alert('Lead restored.');
header('Location: /agent/custom/marketing_leads.php?archived=1');
exit;
