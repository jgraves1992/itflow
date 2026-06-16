<?php

defined('FROM_POST_HANDLER') || die("Direct file access is not allowed");

if (isset($_POST['save_settings_huntress'])) {

    validateCSRFToken($_POST['csrf_token']);

    $huntress_api_key    = sanitizeInput($_POST['config_huntress_api_key']);
    $huntress_api_secret = sanitizeInput($_POST['config_huntress_api_secret']);

    mysqli_query($mysqli, "UPDATE settings SET
        config_huntress_api_key    = '$huntress_api_key',
        config_huntress_api_secret = '$huntress_api_secret'
        WHERE company_id = 1");

    logAction("Settings", "Update", "$session_name updated Huntress integration settings");
    flash_alert("Huntress integration settings saved");
    redirect("/admin/settings_integrations.php");
}
