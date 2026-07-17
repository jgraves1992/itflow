<?php

/*
 * API - payment_methods/read.php
 * List payment method names so callers know valid values for the
 * payment_method field on invoice pay/create endpoints.
 *
 * GET /api/v1/payment_methods/read.php?api_key=KEY
 *
 * The payment_method field in payments is a plain string matching
 * payment_method_name from this table (e.g. "Credit Card", "Stripe", "ACH").
 */

require_once '../validate_api_key.php';
require_once '../require_get_method.php';

$sql = mysqli_query($mysqli,
    "SELECT payment_method_id, payment_method_name
     FROM payment_methods
     ORDER BY payment_method_name
     LIMIT $limit OFFSET $offset"
);

require_once '../read_output.php';
