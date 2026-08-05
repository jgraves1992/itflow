<?php

// Custom functions for vendor seat-sync integrations.
// Included by functions.php loader.

// Updates qty/totals on any recurring invoice items linked to a software license, and recalculates the parent recurring invoice amount
function syncRecurringInvoiceItemsBySoftwareId($software_id, $new_qty) {
    global $mysqli;

    $software_id = intval($software_id);
    $new_qty = floatval($new_qty);

    $sql_items = mysqli_query($mysqli, "SELECT item_id, item_price, item_tax_id, item_recurring_invoice_id
        FROM recurring_invoice_items
        WHERE item_software_id = $software_id
        AND item_archived_at IS NULL
    ");

    $affected_recurring_invoice_ids = [];

    while ($item = mysqli_fetch_assoc($sql_items)) {
        $item_id = intval($item['item_id']);
        $price = floatval($item['item_price']);
        $tax_id = intval($item['item_tax_id']);
        $recurring_invoice_id = intval($item['item_recurring_invoice_id']);

        $subtotal = $price * $new_qty;

        if ($tax_id > 0) {
            $tax_row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT tax_percent FROM taxes WHERE tax_id = $tax_id"));
            $tax_percent = floatval($tax_row['tax_percent'] ?? 0);
            $tax_amount = $subtotal * $tax_percent / 100;
        } else {
            $tax_amount = 0;
        }

        $total = $subtotal + $tax_amount;

        mysqli_query($mysqli, "UPDATE recurring_invoice_items SET
            item_quantity = $new_qty,
            item_subtotal = $subtotal,
            item_tax = $tax_amount,
            item_total = $total
            WHERE item_id = $item_id
        ");

        $affected_recurring_invoice_ids[$recurring_invoice_id] = true;
    }

    foreach (array_keys($affected_recurring_invoice_ids) as $recurring_invoice_id) {
        $discount_row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT recurring_invoice_discount_amount FROM recurring_invoices WHERE recurring_invoice_id = $recurring_invoice_id"));
        $discount = floatval($discount_row['recurring_invoice_discount_amount'] ?? 0);

        $total_row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT SUM(item_total) AS total FROM recurring_invoice_items WHERE item_recurring_invoice_id = $recurring_invoice_id"));
        $new_amount = floatval($total_row['total'] ?? 0) - $discount;

        mysqli_query($mysqli, "UPDATE recurring_invoices SET recurring_invoice_amount = $new_amount WHERE recurring_invoice_id = $recurring_invoice_id");
    }
}

// Recalculates amount (total seats x unit cost) on any recurring expenses tied to a vendor sync source.
function syncRecurringExpensesBySyncSource($sync_source) {
    global $mysqli;

    $sync_source = mysqli_real_escape_string($mysqli, $sync_source);

    $total_row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COALESCE(SUM(software_seats), 0) AS total_seats
        FROM software
        WHERE software_sync_source = '$sync_source'
        AND software_billing_exempt = 0
        AND software_archived_at IS NULL
    "));
    $total_seats = floatval($total_row['total_seats']);

    $sql_expenses = mysqli_query($mysqli, "SELECT recurring_expense_id, recurring_expense_unit_cost
        FROM recurring_expenses
        WHERE recurring_expense_sync_source = '$sync_source'
        AND recurring_expense_archived_at IS NULL
    ");

    while ($exp = mysqli_fetch_assoc($sql_expenses)) {
        $recurring_expense_id = intval($exp['recurring_expense_id']);
        $unit_cost = floatval($exp['recurring_expense_unit_cost']);
        $new_amount = round($total_seats * $unit_cost, 2);

        mysqli_query($mysqli, "UPDATE recurring_expenses SET
            recurring_expense_quantity = $total_seats,
            recurring_expense_amount = $new_amount
            WHERE recurring_expense_id = $recurring_expense_id
        ");
    }
}
