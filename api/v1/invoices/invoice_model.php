<?php

/*
 * API - invoice_model.php
 * Shared field parser for invoice and recurring_invoice create endpoints.
 * Expects $client_id already set (from validate_api_key + require_post_method).
 *
 * Required POST fields:
 *   scope          string   Invoice title / description
 *
 * Optional POST fields:
 *   date           string   YYYY-MM-DD (default: today)
 *   due_date       string   YYYY-MM-DD (default: today + client net_terms)
 *   currency_code  string   (default: client's currency)
 *   discount_amount float   (default: 0)
 *   note           string
 *   category_id    int      (default: 0)
 *   total          float    Used when no items array is provided
 *   send_email     bool     (default: false — callers like Stripe handle their own emails)
 *   items          array    [{name, description, quantity, price, tax_id}]
 *
 * Sets after parsing:
 *   $scope, $date, $due_date, $currency_code, $discount_amount,
 *   $note, $category_id, $send_email, $parsed_items, $invoice_amount
 */

// ------------------------------------------------------------------
// Core fields
// ------------------------------------------------------------------

$scope = isset($_POST['scope']) ? sanitizeInput($_POST['scope']) : '';

$date = (isset($_POST['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date']))
    ? sanitizeInput($_POST['date'])
    : date('Y-m-d');

$discount_amount = isset($_POST['discount_amount']) ? max(0, floatval($_POST['discount_amount'])) : 0;
$note            = isset($_POST['note'])            ? sanitizeInput($_POST['note'])            : '';
$category_id     = isset($_POST['category_id'])     ? intval($_POST['category_id'])            : 0;

// Email suppressed by default — Stripe (or whatever caller) handles its own notifications
$send_email = !empty($_POST['send_email']) && $_POST['send_email'] !== 'false' && $_POST['send_email'] !== '0';

// ------------------------------------------------------------------
// Client-derived defaults (currency, net terms)
// ------------------------------------------------------------------

$client_row = mysqli_fetch_assoc(mysqli_query($mysqli,
    "SELECT client_currency_code, client_net_terms FROM clients WHERE client_id = $client_id LIMIT 1"
));

$currency_code = (isset($_POST['currency_code']) && !empty($_POST['currency_code']))
    ? sanitizeInput($_POST['currency_code'])
    : ($client_row ? sanitizeInput($client_row['client_currency_code']) : 'USD');

$net_terms = $client_row ? intval($client_row['client_net_terms']) : 0;

$due_date = (isset($_POST['due_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['due_date']))
    ? sanitizeInput($_POST['due_date'])
    : date('Y-m-d', strtotime("$date +$net_terms days"));

// ------------------------------------------------------------------
// Line items — calculate subtotals, tax, totals
// ------------------------------------------------------------------

$raw_items     = (isset($_POST['items']) && is_array($_POST['items'])) ? $_POST['items'] : [];
$parsed_items  = [];
$items_total   = 0;

foreach ($raw_items as $item) {
    $item_name = sanitizeInput($item['name'] ?? '');
    if (!$item_name) continue;

    $item_description = sanitizeInput($item['description'] ?? '');
    $item_quantity    = max(0, floatval($item['quantity'] ?? 1));
    $item_price       = floatval($item['price'] ?? 0);
    $item_subtotal    = round($item_quantity * $item_price, 2);
    $item_tax_id      = intval($item['tax_id'] ?? 0);
    $item_tax         = 0;

    if ($item_tax_id > 0) {
        $tax_row = mysqli_fetch_assoc(mysqli_query($mysqli,
            "SELECT tax_rate FROM taxes WHERE tax_id = $item_tax_id LIMIT 1"
        ));
        if ($tax_row) {
            $item_tax = round($item_subtotal * floatval($tax_row['tax_rate']) / 100, 2);
        }
    }

    $item_total   = round($item_subtotal + $item_tax, 2);
    $items_total += $item_total;

    $parsed_items[] = [
        'name'        => $item_name,
        'description' => $item_description,
        'quantity'    => $item_quantity,
        'price'       => $item_price,
        'subtotal'    => $item_subtotal,
        'tax'         => $item_tax,
        'tax_id'      => $item_tax_id,
        'total'       => $item_total,
        'order'       => count($parsed_items) + 1,
    ];
}

// If no items provided, accept a flat `total` field
if (empty($parsed_items) && isset($_POST['total'])) {
    $items_total = floatval($_POST['total']);
}

$invoice_amount = max(0, round($items_total - $discount_amount, 2));
