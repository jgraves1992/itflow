<?php

/*
 * API - invoices/create.php
 * Create a one-time invoice with optional line items.
 *
 * POST /api/v1/invoices/create.php
 * Header: Content-Type: application/json
 * Body:
 * {
 *   "api_key":        "your-key",
 *   "client_id":      5,
 *   "scope":          "Residential Managed Services - January",
 *   "date":           "2026-07-17",       // optional, default today
 *   "due_date":       "2026-07-17",       // optional, default today + net_terms
 *   "currency_code":  "USD",              // optional, default client currency
 *   "discount_amount": 0,                 // optional
 *   "note":           "Thank you!",       // optional
 *   "category_id":    1,                  // optional
 *   "send_email":     false,              // optional, default false
 *   "total":          99.00,              // used when no items array provided
 *   "items": [                            // optional
 *     {"name": "RMM", "quantity": 10, "price": 5.00},
 *     {"name": "Backup", "description": "Cloud backup", "quantity": 1, "price": 50.00, "tax_id": 1}
 *   ]
 * }
 *
 * Returns: {"success": "True", "data": [{"insert_id": 42, "invoice_number": "INV-0042", "total": 100.00}]}
 */

require_once '../validate_api_key.php';
require_once '../require_post_method.php';
require_once "../../../includes/load_global_settings.php";

require_once 'invoice_model.php';

// mark_paid: create invoice already in Paid status and record a payment entry atomically
$mark_paid          = !empty($_POST['mark_paid']) && $_POST['mark_paid'] !== 'false' && $_POST['mark_paid'] !== '0';
$payment_date       = (isset($_POST['payment_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['payment_date']))
                        ? sanitizeInput($_POST['payment_date']) : date('Y-m-d');
$payment_method     = isset($_POST['payment_method'])     ? sanitizeInput($_POST['payment_method'])     : 'Stripe';
$payment_reference  = isset($_POST['payment_reference'])  ? sanitizeInput($_POST['payment_reference'])  : '';
$payment_account_id = isset($_POST['payment_account_id']) ? intval($_POST['payment_account_id'])        : 0;

$invoice_status = $mark_paid ? 'Paid' : 'Sent';
$insert_id      = false;

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

// Atomically increment invoice number
mysqli_query($mysqli, "
    UPDATE settings
    SET config_invoice_next_number = LAST_INSERT_ID(config_invoice_next_number),
        config_invoice_next_number = config_invoice_next_number + 1
    WHERE company_id = 1
");
$new_invoice_number = mysqli_insert_id($mysqli);
$url_key = randomString(32);

$insert_sql = mysqli_query($mysqli, "
    INSERT INTO invoices SET
        invoice_prefix          = '$config_invoice_prefix',
        invoice_number          = $new_invoice_number,
        invoice_scope           = '$scope',
        invoice_date            = '$date',
        invoice_due             = '$due_date',
        invoice_discount_amount = $discount_amount,
        invoice_amount          = $invoice_amount,
        invoice_currency_code   = '$currency_code',
        invoice_note            = '$note',
        invoice_category_id     = $category_id,
        invoice_status          = '$invoice_status',
        invoice_url_key         = '$url_key',
        invoice_client_id       = $client_id
");

if ($insert_sql) {
    $insert_id = mysqli_insert_id($mysqli);

    // Insert line items
    foreach ($parsed_items as $item) {
        mysqli_query($mysqli, "
            INSERT INTO invoice_items SET
                item_name        = '{$item['name']}',
                item_description = '{$item['description']}',
                item_quantity    = {$item['quantity']},
                item_price       = {$item['price']},
                item_subtotal    = {$item['subtotal']},
                item_tax         = {$item['tax']},
                item_total       = {$item['total']},
                item_order       = {$item['order']},
                item_tax_id      = {$item['tax_id']},
                item_invoice_id  = $insert_id
        ");
    }

    $history_status = $mark_paid ? 'Paid' : 'Sent';
    mysqli_query($mysqli, "
        INSERT INTO history SET
            history_status      = '$history_status',
            history_description = 'Invoice created via API ($api_key_name)',
            history_invoice_id  = $insert_id
    ");

    $payment_id = null;
    if ($mark_paid) {
        mysqli_query($mysqli, "
            INSERT INTO payments SET
                payment_date          = '$payment_date',
                payment_amount        = $invoice_amount,
                payment_currency_code = '$currency_code',
                payment_account_id    = $payment_account_id,
                payment_method        = '$payment_method',
                payment_reference     = '$payment_reference',
                payment_invoice_id    = $insert_id
        ");
        $payment_id = mysqli_insert_id($mysqli);
    }

    if ($send_email) {
        $contact_row = mysqli_fetch_assoc(mysqli_query($mysqli, "
            SELECT contact_name, contact_email FROM contacts
            WHERE contact_client_id = $client_id AND contact_primary = 1 LIMIT 1
        "));
        if ($contact_row && !empty($contact_row['contact_email'])) {
            $contact_name  = sanitizeInput($contact_row['contact_name']);
            $contact_email = sanitizeInput($contact_row['contact_email']);
            $base_url      = isset($config_base_url) ? $config_base_url : $_SERVER['HTTP_HOST'];
            $subject = "Invoice $config_invoice_prefix$new_invoice_number";
            $body    = "Hello $contact_name,<br><br>An invoice regarding \"$scope\" has been generated.<br><br>"
                     . "Invoice: $config_invoice_prefix$new_invoice_number<br>"
                     . "Issue Date: $date<br>Due Date: $due_date<br>"
                     . "Total: $currency_code $invoice_amount<br><br>"
                     . "To view your invoice, click <a href='https://$base_url/guest/guest_view_invoice.php?invoice_id=$insert_id&url_key=$url_key'>here</a>.<br><br>"
                     . "--<br>$config_invoice_from_name<br>$config_invoice_from_email";
            addToMailQueue([
                [
                    'from'           => $config_invoice_from_email,
                    'from_name'      => $config_invoice_from_name,
                    'recipient'      => $contact_email,
                    'recipient_name' => $contact_name,
                    'subject'        => $subject,
                    'body'           => $body,
                ]
            ]);
        }
    }

    logAction("Invoice", "Create", "Invoice $config_invoice_prefix$new_invoice_number created via API ($api_key_name)", $client_id, $insert_id);
    logAction("API", "Success", "Created invoice $config_invoice_prefix$new_invoice_number via API ($api_key_name)", $client_id);

    $return_arr['success'] = "True";
    $return_arr['count']   = "1";
    $data = [
        'insert_id'      => $insert_id,
        'invoice_number' => $config_invoice_prefix . $new_invoice_number,
        'total'          => $invoice_amount,
        'due_date'       => $due_date,
        'status'         => $invoice_status,
    ];
    if ($payment_id) {
        $data['payment_id'] = $payment_id;
    }
    $return_arr['data'][] = $data;

} else {
    $return_arr['success'] = "False";
    $return_arr['message'] = "Insert failed. Check that client_id exists and the database schema is up-to-date.";
    if (mysqli_error($mysqli)) {
        error_log("API Invoice Create Error: " . mysqli_error($mysqli));
    }
}

echo json_encode($return_arr);
exit();
