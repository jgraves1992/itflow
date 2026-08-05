<?php

/*
 * ITFlow - GET/POST request handler for Contract Templates
 */

defined('FROM_POST_HANDLER') || die("Direct file access is not allowed");

if (isset($_POST['add_contract_template'])) {

    validateCSRFToken();

    // Sanitize text inputs
    $name = escapeSql($_POST['name']);
    $description = escapeSql($_POST['description']);
    $type = escapeSql($_POST['type']);
    $renewal_frequency = escapeSql($_POST['renewal_frequency']);
    $support_hours = escapeSql($_POST['support_hours']);
    $details = mysqli_escape_string($mysqli, $_POST['details']);

    // Numeric fields cast to integer
    $tmpl_sla_low_id    = intval($_POST['contract_template_sla_low_id']    ?? 0);
    $tmpl_sla_medium_id = intval($_POST['contract_template_sla_medium_id'] ?? 0);
    $tmpl_sla_high_id   = intval($_POST['contract_template_sla_high_id']   ?? 0);
    $tmpl_sla_low_sql    = $tmpl_sla_low_id    > 0 ? $tmpl_sla_low_id    : 'NULL';
    $tmpl_sla_medium_sql = $tmpl_sla_medium_id > 0 ? $tmpl_sla_medium_id : 'NULL';
    $tmpl_sla_high_sql   = $tmpl_sla_high_id   > 0 ? $tmpl_sla_high_id   : 'NULL';
    $rate_standard = intval($_POST['rate_standard']);
    $rate_after_hours = intval($_POST['hourly_rate_after_hours']);
    $net_terms = intval($_POST['net_terms']);

    // Insert into database (numbers not quoted)
    mysqli_query($mysqli, "
        INSERT INTO contract_templates SET
        contract_template_name = '$name',
        contract_template_description = '$description',
        contract_template_details = '$details',
        contract_template_type = '$type',
        contract_template_renewal_frequency = '$renewal_frequency',
        contract_template_sla_low_id    = $tmpl_sla_low_sql,
        contract_template_sla_medium_id = $tmpl_sla_medium_sql,
        contract_template_sla_high_id   = $tmpl_sla_high_sql,
        contract_template_rate_standard = $rate_standard,
        contract_template_rate_after_hours = $rate_after_hours,
        contract_template_support_hours = '$support_hours',
        contract_template_net_terms = $net_terms
    ");

    $contract_template_id = mysqli_insert_id($mysqli);

    // Log action
    logAudit("Contract Template", "Create", "$session_name created contract template $name", 0, $contract_template_id);

    // Flash message
    flashAlert("Contract Template <strong>$name</strong> created");

    // Redirect back
    redirect();
}

if (isset($_POST['edit_contract_template'])) {

    validateCSRFToken();

    $contract_template_id = intval($_POST['contract_template_id']);
    $name            = escapeSql($_POST['name']);
    $description     = escapeSql($_POST['description']);
    $type            = escapeSql($_POST['type']);
    $renewal_frequency= escapeSql($_POST['renewal_frequency']);
    $support_hours   = escapeSql($_POST['support_hours']);
    $details         = mysqli_escape_string($mysqli, $_POST['details']);
    $tmpl_sla_low_id    = intval($_POST['contract_template_sla_low_id']    ?? 0);
    $tmpl_sla_medium_id = intval($_POST['contract_template_sla_medium_id'] ?? 0);
    $tmpl_sla_high_id   = intval($_POST['contract_template_sla_high_id']   ?? 0);
    $tmpl_sla_low_sql    = $tmpl_sla_low_id    > 0 ? $tmpl_sla_low_id    : 'NULL';
    $tmpl_sla_medium_sql = $tmpl_sla_medium_id > 0 ? $tmpl_sla_medium_id : 'NULL';
    $tmpl_sla_high_sql   = $tmpl_sla_high_id   > 0 ? $tmpl_sla_high_id   : 'NULL';
    $rate_standard   = intval($_POST['rate_standard']);
    $rate_after_hours = intval($_POST['rate_after_hours']);
    $net_terms     = intval($_POST['net_terms']);

    mysqli_query($mysqli, "
        UPDATE contract_templates SET
            contract_template_name = '$name',
            contract_template_description = '$description',
            contract_template_details = '$details',
            contract_template_type = '$type',
            contract_template_renewal_frequency = '$renewal_frequency',
            contract_template_sla_low_id    = $tmpl_sla_low_sql,
            contract_template_sla_medium_id = $tmpl_sla_medium_sql,
            contract_template_sla_high_id   = $tmpl_sla_high_sql,
            contract_template_rate_standard = $rate_standard,
            contract_template_rate_after_hours = $rate_after_hours,
            contract_template_support_hours = '$support_hours',
            contract_template_net_terms = $net_terms
        WHERE contract_template_id = $contract_template_id
    ");

    // Log action
    logAudit("Contract Template", "Update", "$session_name updated contract template $name", 0, $contract_template_id);

    // Flash + redirect
    flashAlert("Contract Template <strong>$name</strong> updated");
    redirect();
}

if (isset($_GET['archive_contract_template'])) {

    validateCSRFToken();

    $contract_template_id = intval($_GET['archive_contract_template']);

    $name = escapeSql(getFieldById('contract_templates', $contract_template_id, 'contract_template_name'));

    mysqli_query($mysqli, "
        UPDATE contract_templates SET contract_template_archived_at = NOW()
        WHERE contract_template_id = $contract_template_id
        LIMIT 1
    ");

    logAudit("Contract Template", "Archive", "$session_name archived contract template $name", 0, $contract_template_id);
    flashAlert("Contract Template <strong>$name</strong> archived", "danger");
    redirect();
}

if (isset($_GET['restore_contract_template'])) {

    validateCSRFToken();

    $contract_template_id = intval($_GET['restore_contract_template']);

    $name = escapeSql(getFieldById('contract_templates', $contract_template_id, 'contract_template_name'));

    mysqli_query($mysqli, "
        UPDATE contract_templates SET contract_template_archived_at = NULL
        WHERE contract_template_id = $contract_template_id
        LIMIT 1
    ");

    logAudit("Contract Template", "Restore", "$session_name restored contract template $name", 0, $contract_template_id);
    flashAlert("Contract Template <strong>$name</strong> restored");
    redirect();
}

if (isset($_GET['delete_contract_template'])) {

    validateCSRFToken();
    
    $contract_template_id = intval($_GET['delete_contract_template']);

    $name = escapeSql(getFieldById('contract_templates', $contract_template_id, 'contract_template_name'));

    mysqli_query($mysqli, "
        DELETE FROM contract_templates
        WHERE contract_template_id = $contract_template_id
        LIMIT 1
    ");

    logAudit("Contract Template", "Delete", "$session_name deleted contract template $name", 0, $contract_template_id);
    flashAlert("Contract Template <strong>$name</strong> deleted", "danger");
    redirect();
}

?>
