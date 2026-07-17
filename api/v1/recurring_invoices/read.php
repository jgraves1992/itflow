<?php

/*
 * API - recurring_invoices/read.php
 * Read recurring invoices for a client.
 *
 * GET /api/v1/recurring_invoices/read.php?api_key=KEY
 * GET /api/v1/recurring_invoices/read.php?api_key=KEY&recurring_invoice_id=7
 * GET /api/v1/recurring_invoices/read.php?api_key=KEY&client_id=5&limit=25&offset=0
 */

require_once '../validate_api_key.php';
require_once '../require_get_method.php';

if (isset($_GET['recurring_invoice_id'])) {
    $id  = intval($_GET['recurring_invoice_id']);
    $sql = mysqli_query($mysqli, "
        SELECT * FROM recurring_invoices
        WHERE recurring_invoice_id = $id
          AND recurring_invoice_client_id LIKE '$client_id'
    ");
} else {
    $sql = mysqli_query($mysqli, "
        SELECT * FROM recurring_invoices
        WHERE recurring_invoice_client_id LIKE '$client_id'
        ORDER BY recurring_invoice_id
        LIMIT $limit OFFSET $offset
    ");
}

require_once '../read_output.php';
