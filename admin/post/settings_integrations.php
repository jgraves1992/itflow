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

if (isset($_POST['save_settings_levelio'])) {

    validateCSRFToken($_POST['csrf_token']);

    $levelio_api_key = sanitizeInput($_POST['config_levelio_api_key']);

    mysqli_query($mysqli, "UPDATE settings SET
        config_levelio_api_key = '$levelio_api_key'
        WHERE company_id = 1");

    logAction("Settings", "Update", "$session_name updated Level.io integration settings");
    flash_alert("Level.io integration settings saved");
    redirect("/admin/settings_integrations.php");
}

if (isset($_POST['save_settings_sherweb'])) {

    validateCSRFToken($_POST['csrf_token']);

    $sherweb_client_id        = sanitizeInput($_POST['config_sherweb_client_id']);
    $sherweb_client_secret    = sanitizeInput($_POST['config_sherweb_client_secret']);
    $sherweb_subscription_key = sanitizeInput($_POST['config_sherweb_subscription_key']);

    mysqli_query($mysqli, "UPDATE settings SET
        config_sherweb_client_id        = '$sherweb_client_id',
        config_sherweb_client_secret    = '$sherweb_client_secret',
        config_sherweb_subscription_key = '$sherweb_subscription_key'
        WHERE company_id = 1");

    logAction("Settings", "Update", "$session_name updated Sherweb integration settings");
    flash_alert("Sherweb integration settings saved");
    redirect("/admin/settings_integrations.php");
}
