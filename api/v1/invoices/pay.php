<?php

/*
 * API - invoices/pay.php
 * Mark an existing invoice as paid and record a payment entry.
 *
 * POST /api/v1/invoices/pay.php
 * Header: Content-Type: application/json
 * Body:
 * {
 *   "api_key":             "your-key",
 *   "invoice_id":          42,
 *   "payment_date":        "2026-07-17",   // optional, default today
 *   "payment_amount":      99.00,          // optional, default full invoice amount
 *   "payment_method":      "Stripe",       // optional, default "Stripe"
 *   "payment_reference":   "pi_3abc123",   // optional, e.g. Stripe PaymentIntent/Invoice ID
 *   "payment_account_id":  1,              // optional, default 0 (unassigned)
 *   "expense_vendor_id":   3,              // optional — record gateway fee as expense when provided
 *   "expense_category_id": 8,             // optional — required alongside expense_vendor_id
 *   "expense_amount":      3.22           // optional — fee amount; required to record expense
 * }
 *
 * Returns: {"success": "True", "data": [{"invoice_id": 42, "payment_id": 17, "amount_paid": 99.00}]}
 *
 * Errors if: invoice not found, already paid, or belongs to a different client.
 */

require_once '../validate_api_key.php';
require_once '../require_post_method.php';

$invoice_id = intval($_POST['invoice_id'] ?? 0);

if (!$invoice_id) {
    $return_arr['success'] = "False";
    $return_arr['message'] = "Required field 'invoice_id' is missing.";
    echo json_encode($return_arr);
    exit();
}

// Fetch the invoice — enforce client scope
$invoice = mysqli_fetch_assoc(mysqli_query($mysqli, "
    SELECT * FROM invoices
    WHERE invoice_id = $invoice_id
      AND invoice_client_id LIKE '$client_id'
    LIMIT 1
"));

if (!$invoice) {
    $return_arr['success'] = "False";
    $return_arr['message'] = "Invoice not found or does not belong to this API key's client.";
    echo json_encode($return_arr);
    exit();
}

if ($invoice['invoice_status'] === 'Paid') {
    $return_arr['success'] = "False";
    $return_arr['message'] = "Invoice is already marked as paid.";
    echo json_encode($return_arr);
    exit();
}

// ------------------------------------------------------------------
// Payment fields
// ------------------------------------------------------------------

$payment_date = (isset($_POST['payment_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['payment_date']))
    ? sanitizeInput($_POST['payment_date'])
    : date('Y-m-d');

$payment_amount = isset($_POST['payment_amount'])
    ? round(floatval($_POST['payment_amount']), 2)
    : floatval($invoice['invoice_amount']);

$payment_method    = isset($_POST['payment_method'])    ? sanitizeInput($_POST['payment_method'])    : 'Stripe';
$payment_reference = isset($_POST['payment_reference']) ? sanitizeInput($_POST['payment_reference']) : '';
$payment_account_id = isset($_POST['payment_account_id']) ? intval($_POST['payment_account_id']) : 0;

// Optional gateway fee expense fields
$expense_vendor_id   = isset($_POST['expense_vendor_id'])   ? intval($_POST['expense_vendor_id'])          : 0;
$expense_category_id = isset($_POST['expense_category_id']) ? intval($_POST['expense_category_id'])        : 0;
$expense_amount      = isset($_POST['expense_amount'])       ? round(floatval($_POST['expense_amount']), 2) : 0.0;

$currency_code = sanitizeInput($invoice['invoice_currency_code']);
$resolved_client_id = intval($invoice['invoice_client_id']);

// ------------------------------------------------------------------
// Record payment and mark invoice paid
// ------------------------------------------------------------------

$pay_sql = mysqli_query($mysqli, "
    INSERT INTO payments SET
        payment_date          = '$payment_date',
        payment_amount        = $payment_amount,
        payment_currency_code = '$currency_code',
        payment_account_id    = $payment_account_id,
        payment_method        = '$payment_method',
        payment_reference     = '$payment_reference',
        payment_invoice_id    = $invoice_id
");

if (!$pay_sql) {
    $return_arr['success'] = "False";
    $return_arr['message'] = "Failed to insert payment record.";
    if (mysqli_error($mysqli)) {
        error_log("API Invoice Pay Error: " . mysqli_error($mysqli));
    }
    echo json_encode($return_arr);
    exit();
}

$payment_id = mysqli_insert_id($mysqli);

mysqli_query($mysqli, "UPDATE invoices SET invoice_status = 'Paid' WHERE invoice_id = $invoice_id");

mysqli_query($mysqli, "
    INSERT INTO history SET
        history_status      = 'Paid',
        history_description = 'Payment recorded via API ($api_key_name)',
        history_invoice_id  = $invoice_id
");

// Record gateway fee as an expense when all three fields are provided
$expense_id = null;
if ($expense_vendor_id > 0 && $expense_category_id > 0 && $expense_amount > 0) {
    $expense_desc = sanitizeInput("Gateway fee ($payment_method) - ref: $payment_reference");
    mysqli_query($mysqli, "
        INSERT INTO expenses SET
            expense_date          = '$payment_date',
            expense_description   = '$expense_desc',
            expense_amount        = $expense_amount,
            expense_currency_code = '$currency_code',
            expense_account_id    = $payment_account_id,
            expense_client_id     = $resolved_client_id,
            expense_vendor_id     = $expense_vendor_id,
            expense_category_id   = $expense_category_id,
            expense_reference     = '$payment_reference'
    ");
    $expense_id = mysqli_insert_id($mysqli);
}

logAction("Invoice", "Payment", "Invoice $invoice_id marked paid via API ($api_key_name) — $payment_method $currency_code $payment_amount", $resolved_client_id, $invoice_id);
logAction("API", "Success", "Marked invoice $invoice_id paid via API ($api_key_name)", $resolved_client_id);

$return_arr['success'] = "True";
$return_arr['count']   = "1";
$data = [
    'invoice_id'  => $invoice_id,
    'payment_id'  => $payment_id,
    'amount_paid' => $payment_amount,
    'method'      => $payment_method,
];
if ($expense_id) {
    $data['expense_id']     = $expense_id;
    $data['expense_amount'] = $expense_amount;
}
$return_arr['data'][] = $data;

echo json_encode($return_arr);
exit();
