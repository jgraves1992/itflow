<?php

/*
 * ITFlow - GET/POST request handler for contracts
 */

defined('FROM_POST_HANDLER') || die("Direct file access is not allowed");

if (isset($_POST['add_contract'])) {

    validateCSRFToken($_POST['csrf_token']);
    enforceUserPermission('module_sales', 2);

    $client_id          = intval($_POST['client_id']);
    $contract_name      = sanitizeInput($_POST['contract_name']);
    $contract_type      = sanitizeInput($_POST['contract_type']);
    $contract_status    = sanitizeInput($_POST['contract_status']);
    $start_date         = sanitizeInput($_POST['contract_start_date']);
    $end_date           = sanitizeInput($_POST['contract_end_date']);
    $renewal_frequency  = sanitizeInput($_POST['contract_renewal_frequency']);
    $client_name_snap   = sanitizeInput($_POST['contract_client_name']);
    $client_addr_snap   = sanitizeInput($_POST['contract_client_address']);
    $client_email_snap  = sanitizeInput($_POST['contract_client_email']);
    $client_phone_snap  = sanitizeInput($_POST['contract_client_phone']);
    $contact_name       = sanitizeInput($_POST['contract_contact_name']);
    $agent_name         = sanitizeInput($_POST['contract_agent_name']);
    $net_terms          = sanitizeInput($_POST['contract_net_terms']);
    $support_hours      = sanitizeInput($_POST['contract_support_hours']);
    $details            = mysqli_escape_string($mysqli, $_POST['contract_details'] ?? '');
    $sla_low_resp       = intval($_POST['sla_low_response_time']);
    $sla_low_res        = intval($_POST['sla_low_resolution_time']);
    $sla_med_resp       = intval($_POST['sla_medium_response_time']);
    $sla_med_res        = intval($_POST['sla_medium_resolution_time']);
    $sla_high_resp      = intval($_POST['sla_high_response_time']);
    $sla_high_res       = intval($_POST['sla_high_resolution_time']);
    $rate_standard      = floatval($_POST['contract_rate_standard']);
    $rate_after_hours   = floatval($_POST['contract_rate_after_hours']);

    // Validate required fields
    if (empty($contract_name) || empty($contract_type) || empty($contract_status) || $client_id <= 0) {
        flash_alert("Contract name, type, status, and client are required.", "danger");
        redirect();
    }

    enforceClientAccess();

    // If snapshot fields are empty, auto-fill from live client data
    if (empty($client_name_snap) || empty($client_email_snap)) {
        $sql_client = mysqli_query($mysqli, "SELECT * FROM clients
            LEFT JOIN contacts ON client_id = contact_client_id AND contact_primary = 1
            LEFT JOIN locations ON client_id = location_client_id AND location_primary = 1
            WHERE client_id = $client_id LIMIT 1");
        if ($c = mysqli_fetch_assoc($sql_client)) {
            if (empty($client_name_snap))  $client_name_snap  = sanitizeInput($c['client_name']);
            if (empty($client_email_snap)) $client_email_snap = sanitizeInput($c['contact_email'] ?? '');
            if (empty($client_phone_snap)) $client_phone_snap = sanitizeInput($c['contact_phone'] ?? '');
            if (empty($client_addr_snap)) {
                $addr_parts = array_filter([
                    $c['location_address'] ?? '',
                    $c['location_city'] ?? '',
                    $c['location_state'] ?? '',
                    $c['location_zip'] ?? '',
                ]);
                $client_addr_snap = sanitizeInput(implode(', ', $addr_parts));
            }
            if (empty($contact_name)) $contact_name = sanitizeInput($c['contact_name'] ?? '');
        }
    }

    $start_sql = !empty($start_date) ? "'$start_date'" : 'NULL';
    $end_sql   = !empty($end_date)   ? "'$end_date'"   : 'NULL';

    mysqli_query($mysqli, "INSERT INTO contracts SET
        contract_name                   = '$contract_name',
        contract_status                 = '$contract_status',
        contract_type                   = '$contract_type',
        contract_client_id              = $client_id,
        contract_client_name            = '$client_name_snap',
        contract_client_address         = '$client_addr_snap',
        contract_client_email           = '$client_email_snap',
        contract_client_phone           = '$client_phone_snap',
        contract_contact_name           = '$contact_name',
        contract_agent_name             = '$agent_name',
        contract_sla_low_response_time  = $sla_low_resp,
        contract_sla_low_resolution_time = $sla_low_res,
        contract_sla_medium_response_time = $sla_med_resp,
        contract_sla_medium_resolution_time = $sla_med_res,
        contract_sla_high_response_time = $sla_high_resp,
        contract_sla_high_resolution_time = $sla_high_res,
        contract_rate_standard          = $rate_standard,
        contract_rate_after_hours       = $rate_after_hours,
        contract_net_terms              = '$net_terms',
        contract_support_hours          = '$support_hours',
        contract_renewal_frequency      = '$renewal_frequency',
        contract_start_date             = $start_sql,
        contract_end_date               = $end_sql,
        contract_details                = '$details'
    ");

    $contract_id = mysqli_insert_id($mysqli);

    logAction("Contract", "Create", "$session_name created contract $contract_name", $client_id, $contract_id);

    flash_alert("Contract <strong>$contract_name</strong> created");
    redirect("contract.php?client_id=$client_id&contract_id=$contract_id");
}

if (isset($_POST['edit_contract'])) {

    validateCSRFToken($_POST['csrf_token']);
    enforceUserPermission('module_sales', 2);

    $contract_id        = intval($_POST['contract_id']);
    $contract_name      = sanitizeInput($_POST['contract_name']);
    $contract_type      = sanitizeInput($_POST['contract_type']);
    $contract_status    = sanitizeInput($_POST['contract_status']);
    $start_date         = sanitizeInput($_POST['contract_start_date']);
    $end_date           = sanitizeInput($_POST['contract_end_date']);
    $renewal_frequency  = sanitizeInput($_POST['contract_renewal_frequency']);
    $client_name_snap   = sanitizeInput($_POST['contract_client_name']);
    $client_addr_snap   = sanitizeInput($_POST['contract_client_address']);
    $client_email_snap  = sanitizeInput($_POST['contract_client_email']);
    $client_phone_snap  = sanitizeInput($_POST['contract_client_phone']);
    $contact_name       = sanitizeInput($_POST['contract_contact_name']);
    $agent_name         = sanitizeInput($_POST['contract_agent_name']);
    $net_terms          = sanitizeInput($_POST['contract_net_terms']);
    $support_hours      = sanitizeInput($_POST['contract_support_hours']);
    $details            = mysqli_escape_string($mysqli, $_POST['contract_details'] ?? '');
    $sla_low_resp       = intval($_POST['sla_low_response_time']);
    $sla_low_res        = intval($_POST['sla_low_resolution_time']);
    $sla_med_resp       = intval($_POST['sla_medium_response_time']);
    $sla_med_res        = intval($_POST['sla_medium_resolution_time']);
    $sla_high_resp      = intval($_POST['sla_high_response_time']);
    $sla_high_res       = intval($_POST['sla_high_resolution_time']);
    $rate_standard      = floatval($_POST['contract_rate_standard']);
    $rate_after_hours   = floatval($_POST['contract_rate_after_hours']);

    $start_sql = !empty($start_date) ? "'$start_date'" : 'NULL';
    $end_sql   = !empty($end_date)   ? "'$end_date'"   : 'NULL';

    $sql_check = mysqli_query($mysqli, "SELECT contract_client_id FROM contracts WHERE contract_id = $contract_id LIMIT 1");
    $check = mysqli_fetch_assoc($sql_check);
    $client_id = intval($check['contract_client_id']);

    enforceClientAccess();

    mysqli_query($mysqli, "UPDATE contracts SET
        contract_name                     = '$contract_name',
        contract_status                   = '$contract_status',
        contract_type                     = '$contract_type',
        contract_client_name              = '$client_name_snap',
        contract_client_address           = '$client_addr_snap',
        contract_client_email             = '$client_email_snap',
        contract_client_phone             = '$client_phone_snap',
        contract_contact_name             = '$contact_name',
        contract_agent_name               = '$agent_name',
        contract_sla_low_response_time    = $sla_low_resp,
        contract_sla_low_resolution_time  = $sla_low_res,
        contract_sla_medium_response_time = $sla_med_resp,
        contract_sla_medium_resolution_time = $sla_med_res,
        contract_sla_high_response_time   = $sla_high_resp,
        contract_sla_high_resolution_time = $sla_high_res,
        contract_rate_standard            = $rate_standard,
        contract_rate_after_hours         = $rate_after_hours,
        contract_net_terms                = '$net_terms',
        contract_support_hours            = '$support_hours',
        contract_renewal_frequency        = '$renewal_frequency',
        contract_start_date               = $start_sql,
        contract_end_date                 = $end_sql,
        contract_details                  = '$details'
    WHERE contract_id = $contract_id
    LIMIT 1");

    logAction("Contract", "Update", "$session_name updated contract $contract_name", $client_id, $contract_id);

    flash_alert("Contract <strong>$contract_name</strong> updated");
    redirect();
}

if (isset($_GET['set_contract_status'])) {

    validateCSRFToken($_GET['csrf_token']);
    enforceUserPermission('module_sales', 2);

    $contract_id    = intval($_GET['set_contract_status']);
    $new_status     = sanitizeInput($_GET['status']);
    $allowed        = ['Active', 'Pending', 'Expired', 'Terminated'];

    if (!in_array($new_status, $allowed)) {
        flash_alert("Invalid contract status.", "danger");
        redirect();
    }

    $sql_check = mysqli_query($mysqli, "SELECT contract_client_id, contract_name FROM contracts WHERE contract_id = $contract_id LIMIT 1");
    $check = mysqli_fetch_assoc($sql_check);
    $client_id = intval($check['contract_client_id']);
    $contract_name = sanitizeInput($check['contract_name']);

    enforceClientAccess();

    mysqli_query($mysqli, "UPDATE contracts SET contract_status = '$new_status' WHERE contract_id = $contract_id LIMIT 1");

    logAction("Contract", "Status", "$session_name set contract $contract_name to $new_status", $client_id, $contract_id);

    flash_alert("Contract status updated to <strong>$new_status</strong>");
    redirect();
}

if (isset($_GET['archive_contract'])) {

    validateCSRFToken($_GET['csrf_token']);
    enforceUserPermission('module_sales', 3);

    $contract_id = intval($_GET['archive_contract']);

    $sql_check = mysqli_query($mysqli, "SELECT contract_client_id, contract_name FROM contracts WHERE contract_id = $contract_id LIMIT 1");
    $check = mysqli_fetch_assoc($sql_check);
    $client_id = intval($check['contract_client_id']);
    $contract_name = sanitizeInput($check['contract_name']);

    enforceClientAccess();

    mysqli_query($mysqli, "UPDATE contracts SET contract_archived_at = NOW() WHERE contract_id = $contract_id LIMIT 1");

    logAction("Contract", "Archive", "$session_name archived contract $contract_name", $client_id, $contract_id);

    flash_alert("Contract <strong>$contract_name</strong> archived", "danger");
    redirect("contracts.php?client_id=$client_id");
}

if (isset($_GET['restore_contract'])) {

    validateCSRFToken($_GET['csrf_token']);
    enforceUserPermission('module_sales', 3);

    $contract_id = intval($_GET['restore_contract']);

    $sql_check = mysqli_query($mysqli, "SELECT contract_client_id, contract_name FROM contracts WHERE contract_id = $contract_id LIMIT 1");
    $check = mysqli_fetch_assoc($sql_check);
    $client_id = intval($check['contract_client_id']);
    $contract_name = sanitizeInput($check['contract_name']);

    enforceClientAccess();

    mysqli_query($mysqli, "UPDATE contracts SET contract_archived_at = NULL WHERE contract_id = $contract_id LIMIT 1");

    logAction("Contract", "Restore", "$session_name restored contract $contract_name", $client_id, $contract_id);

    flash_alert("Contract <strong>$contract_name</strong> restored");
    redirect();
}

if (isset($_GET['delete_contract'])) {

    validateCSRFToken($_GET['csrf_token']);
    enforceUserPermission('module_sales', 3);

    $contract_id = intval($_GET['delete_contract']);

    $sql_check = mysqli_query($mysqli, "SELECT contract_client_id, contract_name FROM contracts WHERE contract_id = $contract_id LIMIT 1");
    $check = mysqli_fetch_assoc($sql_check);
    $client_id = intval($check['contract_client_id']);
    $contract_name = sanitizeInput($check['contract_name']);

    enforceClientAccess();

    mysqli_query($mysqli, "DELETE FROM contracts WHERE contract_id = $contract_id LIMIT 1");

    logAction("Contract", "Delete", "$session_name deleted contract $contract_name", $client_id, 0);

    flash_alert("Contract <strong>$contract_name</strong> deleted", "danger");
    redirect("contracts.php?client_id=$client_id");
}
