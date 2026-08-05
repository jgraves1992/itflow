<?php

defined('FROM_POST_HANDLER') || die("Direct file access is not allowed");

// --- Shared helper: load the active Stripe payment provider ---
function getAgentStripeProvider() {
    global $mysqli;
    return mysqli_fetch_assoc(mysqli_query($mysqli, "
        SELECT * FROM payment_providers
        WHERE payment_provider_name = 'Stripe'
        AND payment_provider_active = 1
        LIMIT 1
    "));
}

// 1. Create a Stripe customer record for a client
if (isset($_POST['create_stripe_customer'])) {

    validateCSRFToken();
    enforceUserPermission('module_sales', 2);

    $client_id = intval($_POST['client_id'] ?? 0);
    if (!$client_id) {
        flashAlert("Client not specified.", 'danger');
        redirect();
    }

    $stripe_provider = getAgentStripeProvider();
    if (!$stripe_provider) {
        flashAlert("Stripe is not configured in Admin → Payment Providers.", 'danger');
        redirect("client_autopay.php?client_id=$client_id");
    }

    $stripe_provider_id = intval($stripe_provider['payment_provider_id']);
    $stripe_secret_key  = $stripe_provider['payment_provider_private_key'];

    // Already has a customer?
    $existing = mysqli_fetch_assoc(mysqli_query($mysqli, "
        SELECT payment_provider_client FROM client_payment_provider
        WHERE client_id = $client_id AND payment_provider_id = $stripe_provider_id
        LIMIT 1
    "));

    if ($existing) {
        flashAlert("Stripe customer already exists for this client.", 'danger');
        redirect("client_autopay.php?client_id=$client_id");
    }

    // Load client name + primary contact email
    $client_row = mysqli_fetch_assoc(mysqli_query($mysqli, "
        SELECT clients.client_name, contacts.contact_email
        FROM clients
        LEFT JOIN contacts ON contacts.contact_client_id = clients.client_id AND contact_primary = 1
        WHERE clients.client_id = $client_id
        LIMIT 1
    "));
    $client_name  = escapeSql($client_row['client_name'] ?? '');
    $client_email = escapeSql($client_row['contact_email'] ?? '');

    try {
        require_once __DIR__ . '/../../includes/stripe_init.php';
        $stripe   = new \Stripe\StripeClient($stripe_secret_key);
        $customer = $stripe->customers->create([
            'name'     => $client_name,
            'email'    => $client_email,
            'metadata' => [
                'itflow_client_id' => $client_id,
                'created_by'       => $session_name,
            ],
        ]);

        $stripe_customer_id = escapeSql($customer->id);

        mysqli_query($mysqli, "
            INSERT INTO client_payment_provider
            SET client_id                       = $client_id,
                payment_provider_id             = $stripe_provider_id,
                payment_provider_client         = '$stripe_customer_id',
                client_payment_provider_created_at = NOW()
        ");

        logAudit("Stripe", "Create", "$session_name created Stripe customer for $client_name ($stripe_customer_id)", $client_id);
        flashAlert("Stripe customer created.");

    } catch (Exception $e) {
        error_log("Stripe error creating customer for client $client_id: " . $e->getMessage());
        logApp("Stripe", "error", "Failed to create Stripe customer for client $client_id: " . $e->getMessage());
        flashAlert("Error creating Stripe customer — check the error log.", 'danger');
    }

    redirect("client_autopay.php?client_id=$client_id");
}

// 2. Save card after Stripe Checkout redirects back
if (isset($_GET['stripe_save_card'])) {

    $client_id           = intval($_GET['client_id'] ?? 0);
    $checkout_session_id = escapeSql($_GET['session_id'] ?? '');

    if (!$client_id || !$checkout_session_id) {
        flashAlert("Invalid Stripe return parameters.", 'danger');
        redirect("client_autopay.php?client_id=$client_id");
    }

    $stripe_provider = getAgentStripeProvider();
    if (!$stripe_provider) {
        flashAlert("Stripe provider not configured.", 'danger');
        redirect("client_autopay.php?client_id=$client_id");
    }

    $stripe_provider_id = intval($stripe_provider['payment_provider_id']);
    $stripe_secret_key  = $stripe_provider['payment_provider_private_key'];

    $client_provider = mysqli_fetch_assoc(mysqli_query($mysqli, "
        SELECT payment_provider_client FROM client_payment_provider
        WHERE client_id = $client_id AND payment_provider_id = $stripe_provider_id
        LIMIT 1
    "));
    $stripe_customer_id = escapeSql($client_provider['payment_provider_client'] ?? '');

    if (!$stripe_customer_id) {
        flashAlert("Stripe customer not found for this client.", 'danger');
        redirect("client_autopay.php?client_id=$client_id");
    }

    try {
        require_once __DIR__ . '/../../includes/stripe_init.php';
        $stripe = new \Stripe\StripeClient($stripe_secret_key);

        $checkout_session  = $stripe->checkout->sessions->retrieve($checkout_session_id, []);
        $setup_intent      = $stripe->setupIntents->retrieve($checkout_session->setup_intent, []);
        $payment_method_id = escapeSql($setup_intent->payment_method);

        $stripe->paymentMethods->attach($payment_method_id, ['customer' => $stripe_customer_id]);

        $pm = $stripe->paymentMethods->retrieve($payment_method_id, []);
        if ($pm->type === 'us_bank_account') {
            $bank_name = escapeSql($pm->us_bank_account->bank_name);
            $last4     = escapeSql($pm->us_bank_account->last4);
            $desc      = "ACH - $bank_name ****$last4";
        } else {
            $brand     = escapeSql($pm->card->brand);
            $last4     = escapeSql($pm->card->last4);
            $exp_month = escapeSql($pm->card->exp_month);
            $exp_year  = escapeSql($pm->card->exp_year);
            $desc      = "$brand - $last4 | Exp $exp_month/$exp_year";
        }
        $desc = escapeSql($desc);

        mysqli_query($mysqli, "
            INSERT INTO client_saved_payment_methods
            SET saved_payment_provider_method = '$payment_method_id',
                saved_payment_description     = '$desc',
                saved_payment_client_id       = $client_id,
                saved_payment_provider_id     = $stripe_provider_id,
                saved_payment_created_at      = NOW()
        ");

        logAudit("Stripe", "Update", "$session_name saved payment method for client $client_id ($desc)", $client_id);
        flashAlert("Payment method saved.");

    } catch (Exception $e) {
        error_log("Stripe error saving payment method for client $client_id: " . $e->getMessage());
        logApp("Stripe", "error", "Exception saving payment method for client $client_id: " . $e->getMessage());
        flashAlert("Error saving payment method — check the error log.", 'danger');
    }

    redirect("client_autopay.php?client_id=$client_id");
}

// 3. Remove a saved payment method
if (isset($_GET['stripe_remove_pm'])) {

    validateCSRFToken();
    enforceUserPermission('module_sales', 2);

    $client_id      = intval($_GET['client_id'] ?? 0);
    $payment_method = escapeSql($_GET['pm'] ?? '');

    if (!$client_id || !$payment_method) {
        flashAlert("Missing parameters.", 'danger');
        redirect();
    }

    $stripe_provider = getAgentStripeProvider();
    if (!$stripe_provider) {
        flashAlert("Stripe not configured.", 'danger');
        redirect("client_autopay.php?client_id=$client_id");
    }

    $stripe_provider_id = intval($stripe_provider['payment_provider_id']);
    $stripe_secret_key  = $stripe_provider['payment_provider_private_key'];

    try {
        require_once __DIR__ . '/../../includes/stripe_init.php';
        $stripe = new \Stripe\StripeClient($stripe_secret_key);
        $stripe->paymentMethods->detach($payment_method, []);
    } catch (Exception $e) {
        error_log("Stripe error detaching PM $payment_method for client $client_id: " . $e->getMessage());
        logApp("Stripe", "error", "Exception detaching PM $payment_method: " . $e->getMessage());
    }

    mysqli_query($mysqli, "
        DELETE FROM client_saved_payment_methods
        WHERE saved_payment_client_id    = $client_id
        AND   saved_payment_provider_id  = $stripe_provider_id
        AND   saved_payment_provider_method = '$payment_method'
    ");

    $sql_ri = mysqli_query($mysqli, "SELECT recurring_invoice_id FROM recurring_invoices WHERE recurring_invoice_client_id = $client_id");
    while ($ri = mysqli_fetch_assoc($sql_ri)) {
        $ri_id = intval($ri['recurring_invoice_id']);
        mysqli_query($mysqli, "DELETE FROM recurring_payments WHERE recurring_payment_method = 'Credit Card' AND recurring_payment_recurring_invoice_id = $ri_id");
    }

    logAudit("Stripe", "Update", "$session_name removed saved Stripe PM ($payment_method) for client $client_id", $client_id);
    flashAlert("Payment method removed.");

    redirect("client_autopay.php?client_id=$client_id");
}
