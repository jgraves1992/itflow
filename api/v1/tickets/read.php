<?php

require_once '../validate_api_key.php';

require_once '../require_get_method.php';


// Specific ticket via ID (single)
if (isset($_GET['ticket_id'])) {
    $id = intval($_GET['ticket_id']);
    $sql = mysqli_query(
        $mysqli,
        "SELECT * FROM tickets
        LEFT JOIN ticket_statuses ON ticket_status = ticket_status_id
        WHERE ticket_id = '$id' AND 1=1 " . apiClientScopeSql('ticket_client_id') . ""
    );

} elseif (isset($_GET['ticket_subject'])) {
    // Subject search — used by integrations to find existing tickets before creating duplicates
    $subject = addcslashes(mysqli_real_escape_string($mysqli, $_GET['ticket_subject']), '%_');
    $open_only = isset($_GET['open_only'])
        ? "AND ticket_resolved_at IS NULL AND ticket_closed_at IS NULL"
        : "";
    $sql = mysqli_query(
        $mysqli,
        "SELECT * FROM tickets
        WHERE ticket_subject LIKE '%$subject%'
        $open_only " . apiClientScopeSql('ticket_client_id') . "
        ORDER BY ticket_id DESC LIMIT $limit OFFSET $offset"
    );

} else {
    // All tickets (by client ID if given, or all in general if key permits)
    $sql = mysqli_query($mysqli, "SELECT * FROM tickets WHERE 1=1 " . apiClientScopeSql('ticket_client_id') . " ORDER BY ticket_id LIMIT $limit OFFSET $offset");
}

// Output
require_once "../read_output.php";

