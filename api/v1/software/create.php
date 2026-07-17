<?php

/*
 * API - software/create.php
 * Create a software / license record for a client.
 *
 * POST /api/v1/software/create.php
 * Header: Content-Type: application/json
 * Body:
 * {
 *   "api_key":           "your-key",
 *   "client_id":         5,
 *   "name":              "Protection Starter",          // required
 *   "type":              "Software as a Service (SaaS)", // optional
 *   "license_type":      "Device",                       // optional — Device|User|Site|Concurrent|Trial|Perpetual|Usage-based
 *   "key":               "sub_1abc...",                  // optional — license key or subscription ID
 *   "seats":             0,                              // optional
 *   "purchase_reference":"in_1abc...",                   // optional — invoice/PO reference
 *   "purchase":          "2026-07-17",                   // optional — purchase date
 *   "expire":            "2027-07-17",                   // optional — leave out or null for no expiry
 *   "notes":             "",                             // optional
 *   "vendor_id":         0,                              // optional
 *   "sync_source":       "Level.io",                     // optional — Level.io|Huntress|Sherweb etc.
 *   "sync_external_id":  "Z2lkOi8v..."                  // optional — external group/org/subscription ID
 * }
 *
 * Returns: {"success": "True", "data": [{"insert_id": 12}]}
 */

require_once '../validate_api_key.php';
require_once '../require_post_method.php';

$name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';

if (!$name) {
    $return_arr['success'] = "False";
    $return_arr['message'] = "Required field 'name' is missing.";
    echo json_encode($return_arr);
    exit();
}

if (!$client_id) {
    $return_arr['success'] = "False";
    $return_arr['message'] = "Required field 'client_id' is missing or API key is not scoped to a client.";
    echo json_encode($return_arr);
    exit();
}

$type              = isset($_POST['type'])              ? sanitizeInput($_POST['type'])              : '';
$license_type      = isset($_POST['license_type'])      ? sanitizeInput($_POST['license_type'])      : '';
$key               = isset($_POST['key'])               ? sanitizeInput($_POST['key'])               : '';
$seats             = isset($_POST['seats'])             ? intval($_POST['seats'])                    : 0;
$purchase_reference= isset($_POST['purchase_reference'])? sanitizeInput($_POST['purchase_reference']): '';
$notes             = isset($_POST['notes'])             ? sanitizeInput($_POST['notes'])             : '';
$vendor_id         = isset($_POST['vendor_id'])         ? intval($_POST['vendor_id'])                : 0;
$sync_source       = isset($_POST['sync_source'])       ? sanitizeInput($_POST['sync_source'])       : '';
$sync_external_id  = isset($_POST['sync_external_id'])  ? sanitizeInput($_POST['sync_external_id'])  : '';

$purchase = (isset($_POST['purchase']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['purchase']))
    ? "'" . sanitizeInput($_POST['purchase']) . "'"
    : 'NULL';

// expire: null means no expiry date tracked
$expire = (!empty($_POST['expire']) && $_POST['expire'] !== 'null' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['expire']))
    ? "'" . sanitizeInput($_POST['expire']) . "'"
    : 'NULL';

mysqli_query($mysqli, "
    INSERT INTO software SET
        software_name              = '$name',
        software_type              = '$type',
        software_license_type      = '$license_type',
        software_key               = '$key',
        software_seats             = $seats,
        software_purchase_reference= '$purchase_reference',
        software_purchase          = $purchase,
        software_expire            = $expire,
        software_notes             = '$notes',
        software_vendor_id         = $vendor_id,
        software_sync_source       = '$sync_source',
        software_sync_external_id  = '$sync_external_id',
        software_client_id         = $client_id
");

if (mysqli_affected_rows($mysqli) > 0) {
    $insert_id = mysqli_insert_id($mysqli);

    logAction("Software", "Create", "Software '$name' created via API ($api_key_name)", $client_id, $insert_id);
    logAction("API", "Success", "Created software '$name' via API ($api_key_name)", $client_id);

    $return_arr['success'] = "True";
    $return_arr['count']   = "1";
    $return_arr['data'][]  = ['insert_id' => $insert_id];
} else {
    $return_arr['success'] = "False";
    $return_arr['message'] = "Insert failed. Check that client_id exists.";
    if (mysqli_error($mysqli)) {
        error_log("API Software Create Error: " . mysqli_error($mysqli));
    }
}

echo json_encode($return_arr);
exit();
