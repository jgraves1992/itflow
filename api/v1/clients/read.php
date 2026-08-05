<?php

require_once '../validate_api_key.php';

require_once '../require_get_method.php';

// Specific client via name (single)
if (isset($_GET['client_name'])) {
    $name = mysqli_real_escape_string($mysqli, $_GET['client_name']);
    $sql = mysqli_query($mysqli, "SELECT * FROM clients WHERE client_name = '$name' AND 1=1 " . apiClientScopeSql('client_id') . "");

} elseif (!empty($client_id)) {
    // Specific client via ID (single)
    $sql = mysqli_query($mysqli, "SELECT * FROM clients WHERE client_id = $client_id AND 1=1 " . apiClientScopeSql('client_id') . " LIMIT 1");

} else {
    // All clients
    $sql = mysqli_query($mysqli, "SELECT * FROM clients WHERE 1=1 " . apiClientScopeSql('client_id') . " ORDER BY client_id LIMIT $limit OFFSET $offset");
}

// Output
require_once "../read_output.php";

