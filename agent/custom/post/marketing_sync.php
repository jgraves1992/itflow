<?php
defined('FROM_POST_HANDLER') or die('Direct access not permitted');

if (!isset($_POST['sync_marketing_leads'])) return;

validateCSRFToken($_POST['csrf_token'] ?? '');
enforceUserPermission('module_client', 2);

// Import ITFlow clients/leads that don't already have a marketing_leads record
mysqli_query($mysqli,
    "INSERT INTO marketing_leads
        (lead_name, lead_email, lead_phone, lead_company, lead_status, lead_unsubscribe_token, lead_client_id)
     SELECT
        COALESCE(NULLIF(ct.contact_name, ''), c.client_name),
        ct.contact_email,
        COALESCE(ct.contact_phone, ''),
        c.client_name,
        IF(c.client_lead = 1, 'new', 'converted'),
        LOWER(HEX(RANDOM_BYTES(32))),
        c.client_id
     FROM clients c
     LEFT JOIN contacts ct
        ON ct.contact_client_id = c.client_id AND ct.contact_primary = 1
     WHERE c.client_archived_at IS NULL
       AND ct.contact_email IS NOT NULL
       AND ct.contact_email != ''
       AND c.client_id NOT IN (
           SELECT lead_client_id FROM marketing_leads WHERE lead_client_id IS NOT NULL
       )");

$imported = mysqli_affected_rows($mysqli);

$_SESSION['success'] = "Synced <strong>$imported</strong> client(s) from ITFlow into marketing leads.";
header('Location: /agent/custom/marketing_leads.php');
exit;
