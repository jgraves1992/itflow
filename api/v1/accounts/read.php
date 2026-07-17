<?php

/*
 * API - accounts/read.php
 * List bank/ledger accounts so callers can resolve account_id for payment endpoints.
 *
 * GET /api/v1/accounts/read.php?api_key=KEY
 * GET /api/v1/accounts/read.php?api_key=KEY&account_id=3
 *
 * Returns account_id and account_name for each active account.
 */

require_once '../validate_api_key.php';
require_once '../require_get_method.php';

if (isset($_GET['account_id'])) {
    $id  = intval($_GET['account_id']);
    $sql = mysqli_query($mysqli,
        "SELECT account_id, account_name, account_type, account_number
         FROM accounts
         WHERE account_id = $id AND account_archived_at IS NULL"
    );
} else {
    $sql = mysqli_query($mysqli,
        "SELECT account_id, account_name, account_type, account_number
         FROM accounts
         WHERE account_archived_at IS NULL
         ORDER BY account_name
         LIMIT $limit OFFSET $offset"
    );
}

require_once '../read_output.php';
