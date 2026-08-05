<?php

defined('FROM_POST_HANDLER') || die("Direct file access is not allowed");

if (isset($_GET['stripe_remove_pm'])) {

    validateCSRFToken();

    $stripe_vars = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT payment_provider_id, payment_provider_active, payment_provider_private_key FROM payment_providers LIMIT 1"));
    $stripe_provider_id = $stripe_vars ? intval($stripe_vars['payment_provider_id']) : 0;
    $config_stripe_enable = $stripe_vars ? intval($stripe_vars['payment_provider_active']) : 0;
    $config_stripe_secret = $stripe_vars ? escapeHtml($stripe_vars['payment_provider_private_key']) : '';

    if (!$config_stripe_enable) {
        flashAlert("Stripe not enabled", 'error');
        redirect();
    }

    $client_id = intval($_GET['client_id']);
    $payment_method = escapeSql($_GET['pm']);

    try {
        // Initialize stripe
        require_once '../includes/stripe_init.php';
        $stripe = new \Stripe\StripeClient($config_stripe_secret);

        // Detach PM
        $stripe->paymentMethods->detach($payment_method, []);

    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Stripe payment error - encountered exception when removing payment method info for $payment_method: $error");
        logApp("Stripe", "error", "Exception removing payment method for $payment_method: $error");
    }

    // Remove saved payment method from ITFlow (new table)
    mysqli_query($mysqli, "DELETE FROM client_saved_payment_methods WHERE saved_payment_client_id = $client_id AND saved_payment_provider_id = $stripe_provider_id");

    // Remove Auto Pay on recurring invoices that use Stripe
    $sql_recurring_invoices = mysqli_query($mysqli, "SELECT recurring_invoice_id FROM recurring_invoices WHERE recurring_invoice_client_id = $client_id");
    while ($row = mysqli_fetch_assoc($sql_recurring_invoices)) {
        $recurring_invoice_id = intval($row['recurring_invoice_id']);
        mysqli_query($mysqli, "DELETE FROM recurring_payments WHERE recurring_payment_method = 'Credit Card' AND recurring_payment_recurring_invoice_id = $recurring_invoice_id");
    }

    logAudit("Stripe", "Update", "$session_name deleted saved Stripe payment method (PM: $payment_method)", $client_id);

    flashAlert("Payment method removed", 'error');

    redirect();

}

if (isset($_GET['stripe_reset_customer'])) {

    validateCSRFToken();

    $client_id = intval($_GET['client_id']);

    $stripe_vars = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT payment_provider_id FROM payment_providers LIMIT 1"));
    $stripe_provider_id = $stripe_vars ? intval($stripe_vars['payment_provider_id']) : 0;

    // Remove all saved payment methods and customer link for this client
    if ($stripe_provider_id) {
        mysqli_query($mysqli, "DELETE FROM client_saved_payment_methods WHERE saved_payment_client_id = $client_id AND saved_payment_provider_id = $stripe_provider_id");
        mysqli_query($mysqli, "DELETE FROM client_payment_provider WHERE client_id = $client_id AND payment_provider_id = $stripe_provider_id");
    }

    // Remove Auto Pay on recurring invoices
    $sql_recurring_invoices = mysqli_query($mysqli, "SELECT recurring_invoice_id FROM recurring_invoices WHERE recurring_invoice_client_id = $client_id");
    while ($row = mysqli_fetch_assoc($sql_recurring_invoices)) {
        $recurring_invoice_id = intval($row['recurring_invoice_id']);
        mysqli_query($mysqli, "DELETE FROM recurring_payments WHERE recurring_payment_method = 'Credit Card' AND recurring_payment_recurring_invoice_id = $recurring_invoice_id");
    }

    logAudit("Stripe", "Delete", "$session_name reset Stripe settings for client", $client_id);

    flashAlert("Reset client Stripe settings", 'error');

    redirect();

}
