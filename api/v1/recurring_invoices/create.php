<?php

/*
 * API - recurring_invoices/create.php
 * Create a recurring invoice template with optional line items.
 * The cron job generates real invoices from this template on each next_date.
 *
 * POST /api/v1/recurring_invoices/create.php
 * Header: Content-Type: application/json
 * Body:
 * {
 *   "api_key":         "your-key",
 *   "client_id":       5,
 *   "scope":           "Residential Managed Services",
 *   "frequency":       "monthly",     // daily|weekly|monthly|quarterly|annually
 *   "next_date":       "2026-08-01",  // date of first generation
 *   "currency_code":   "USD",         // optional
 *   "discount_amount": 0,             // optional
 *   "note":            "",            // optional
 *   "category_id":     1,             // optional
 *   "email_notify":    false,         // optional, default false (Stripe handles emails)
 *   "total":           99.00,         // used when no items array provided
 *   "items": [                        // optional
 *     {"name": "RMM", "quantity": 10, "price": 5.00},
 *     {"name": "Backup", "quantity": 1, "price": 50.00, "tax_id": 1}
 *   ]
 * }
 *
 * Returns: {"success": "True", "data": [{"insert_id": 7, "recurring_number": "RINV-0007"}]}
 */

require_once '../validate_api_key.php';
require_once '../require_post_method.php';
require_once "../../../includes/load_global_settings.php";

// Reuse the shared invoice field parser (scope, date, currency, items, amount, etc.)
require_once '../invoices/invoice_model.php';

// ------------------------------------------------------------------
// Recurring-specific fields
// ------------------------------------------------------------------

// Frequency — stored as lowercase (month, year, etc.) to match ITFlow's native format.
// The display layer appends "ly" (ucwords($freq)."ly") and dashboard queries filter on lowercase.
// MySQL's INTERVAL unit is case-insensitive so the cron works with either case.
$frequency_map = [
    'daily'     => 'day',
    'day'       => 'day',
    'weekly'    => 'week',
    'week'      => 'week',
    'monthly'   => 'month',
    'month'     => 'month',
    'quarterly' => 'quarter',
    'quarter'   => 'quarter',
    'annually'  => 'year',
    'yearly'    => 'year',
    'year'      => 'year',
    // Accept uppercase DB values from callers who read them back out
    'DAY'       => 'day',
    'WEEK'      => 'week',
    'MONTH'     => 'month',
    'QUARTER'   => 'quarter',
    'YEAR'      => 'year',
];

$frequency_input = strtolower(trim($_POST['frequency'] ?? ''));
$frequency       = $frequency_map[$frequency_input] ?? '';

$next_date = (isset($_POST['next_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['next_date']))
    ? sanitizeInput($_POST['next_date'])
    : '';

// email_notify defaults off — Stripe (or whatever upstream) sends its own emails
$email_notify = !empty($_POST['email_notify']) && $_POST['email_notify'] !== 'false' && $_POST['email_notify'] !== '0' ? 1 : 0;

// ------------------------------------------------------------------
// Validation
// ------------------------------------------------------------------

$insert_id = false;

if (empty($scope)) {
    $return_arr['success'] = "False";
    $return_arr['message'] = "Required field 'scope' is missing.";
    echo json_encode($return_arr);
    exit();
}

if (!$client_id) {
    $return_arr['success'] = "False";
    $return_arr['message'] = "Required field 'client_id' is missing or API key is not scoped to a client.";
    echo json_encode($return_arr);
    exit();
}

if (empty($frequency)) {
    $return_arr['success'] = "False";
    $return_arr['message'] = "Required field 'frequency' is missing or invalid. Accepted values: daily, weekly, monthly, quarterly, annually.";
    echo json_encode($return_arr);
    exit();
}

if (empty($next_date)) {
    $return_arr['success'] = "False";
    $return_arr['message'] = "Required field 'next_date' is missing or invalid. Expected format: YYYY-MM-DD.";
    echo json_encode($return_arr);
    exit();
}

// ------------------------------------------------------------------
// Atomically increment recurring invoice number
// ------------------------------------------------------------------

mysqli_query($mysqli, "
    UPDATE settings
    SET config_recurring_invoice_next_number = LAST_INSERT_ID(config_recurring_invoice_next_number),
        config_recurring_invoice_next_number = config_recurring_invoice_next_number + 1
    WHERE company_id = 1
");
$new_recurring_number = mysqli_insert_id($mysqli);

$insert_sql = mysqli_query($mysqli, "
    INSERT INTO recurring_invoices SET
        recurring_invoice_prefix        = '$config_recurring_invoice_prefix',
        recurring_invoice_number        = $new_recurring_number,
        recurring_invoice_scope         = '$scope',
        recurring_invoice_frequency     = '$frequency',
        recurring_invoice_next_date     = '$next_date',
        recurring_invoice_status        = 1,
        recurring_invoice_amount        = $invoice_amount,
        recurring_invoice_discount_amount = $discount_amount,
        recurring_invoice_currency_code = '$currency_code',
        recurring_invoice_note          = '$note',
        recurring_invoice_category_id   = $category_id,
        recurring_invoice_email_notify  = $email_notify,
        recurring_invoice_client_id     = $client_id
");

if ($insert_sql) {
    $insert_id = mysqli_insert_id($mysqli);

    // Insert line items into recurring_invoice_items
    foreach ($parsed_items as $item) {
        mysqli_query($mysqli, "
            INSERT INTO recurring_invoice_items SET
                item_name                  = '{$item['name']}',
                item_description           = '{$item['description']}',
                item_quantity              = {$item['quantity']},
                item_price                 = {$item['price']},
                item_subtotal              = {$item['subtotal']},
                item_tax                   = {$item['tax']},
                item_total                 = {$item['total']},
                item_order                 = {$item['order']},
                item_tax_id                = {$item['tax_id']},
                item_recurring_invoice_id  = $insert_id
        ");
    }

    logAction("Recurring Invoice", "Create", "Recurring invoice $config_recurring_invoice_prefix$new_recurring_number created via API ($api_key_name)", $client_id, $insert_id);
    logAction("API", "Success", "Created recurring invoice $config_recurring_invoice_prefix$new_recurring_number via API ($api_key_name)", $client_id);

    $return_arr['success'] = "True";
    $return_arr['count']   = "1";
    $return_arr['data'][]  = [
        'insert_id'        => $insert_id,
        'recurring_number' => $config_recurring_invoice_prefix . $new_recurring_number,
        'frequency'        => $frequency,
        'next_date'        => $next_date,
        'total'            => $invoice_amount,
    ];

} else {
    $return_arr['success'] = "False";
    $return_arr['message'] = "Insert failed. Check that client_id exists and the database schema is up-to-date.";
    if (mysqli_error($mysqli)) {
        error_log("API Recurring Invoice Create Error: " . mysqli_error($mysqli));
    }
}

echo json_encode($return_arr);
exit();
