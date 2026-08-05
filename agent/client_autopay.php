<?php

require_once "includes/inc_all_client.php";

// Perms
enforceUserPermission('module_sales');

// Initialize stripe
require_once '../includes/stripe_init.php';

// Get Stripe vars (moved to payment_providers table in 26.08)
$stripe_vars = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT payment_provider_id, payment_provider_active, payment_provider_public_key, payment_provider_private_key FROM payment_providers LIMIT 1"));
$config_stripe_enable      = $stripe_vars ? intval($stripe_vars['payment_provider_active'])        : 0;
$config_stripe_publishable = $stripe_vars ? escapeHtml($stripe_vars['payment_provider_public_key']) : '';
$config_stripe_secret      = $stripe_vars ? $stripe_vars['payment_provider_private_key']            : '';
$stripe_provider_id        = $stripe_vars ? intval($stripe_vars['payment_provider_id'])             : 0;

// Get client's Stripe relationship (client_payment_provider) and saved PM (client_saved_payment_methods)
$stripe_client_details = mysqli_fetch_assoc(mysqli_query($mysqli, "
    SELECT cpp.payment_provider_client AS stripe_id,
           spm.saved_payment_provider_method AS stripe_pm,
           spm.saved_payment_description AS stripe_pm_description
    FROM client_payment_provider cpp
    JOIN payment_providers pp ON pp.payment_provider_id = cpp.payment_provider_id
    LEFT JOIN client_saved_payment_methods spm
        ON spm.saved_payment_client_id = $client_id
        AND spm.saved_payment_provider_id = cpp.payment_provider_id
    WHERE cpp.client_id = $client_id
    LIMIT 1
"));
if ($stripe_client_details) {
    $stripe_id = escapeSql($stripe_client_details['stripe_id']);
    $stripe_pm = escapeSql($stripe_client_details['stripe_pm']);
}

// Stripe not enabled in settings
if (!$config_stripe_enable || !$config_stripe_publishable || !$config_stripe_secret) {
    echo "Stripe payment error - Stripe is not enabled, please talk to your helpdesk for further information.";
    include_once '../includes/footer.php';
    exit();
}

// Create checkout session server-side when in State 2 (customer exists, no saved PM)
$checkout_client_secret = null;
$checkout_error         = null;
if ($stripe_client_details && !empty($stripe_id) && empty($stripe_pm)) {
    try {
        $stripe_obj = new \Stripe\StripeClient($config_stripe_secret);

        $client_currency_row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT client_currency_code FROM clients WHERE client_id = $client_id LIMIT 1"));
        $client_currency     = strtolower($client_currency_row['client_currency_code'] ?? 'usd');

        $return_url = "https://$config_base_url/agent/post.php?stripe_save_card&client_id=$client_id&session_id={CHECKOUT_SESSION_ID}";

        $checkout_session       = $stripe_obj->checkout->sessions->create([
            'currency'             => $client_currency,
            'mode'                 => 'setup',
            'ui_mode'              => 'embedded_page',
            'return_url'           => $return_url,
            'payment_method_types' => ['card', 'us_bank_account'],
            'customer'             => $stripe_id,
        ]);
        $checkout_client_secret = $checkout_session->client_secret ?? null;
        if (!$checkout_client_secret) {
            $checkout_error = "Stripe session created but no client_secret returned (ui_mode may not support embedded checkout).";
        }

    } catch (\Throwable $e) {
        error_log("Stripe error creating agent checkout session for client $client_id: " . $e->getMessage());
        $checkout_error = escapeHtml($e->getMessage());
    }
}

?>

<div class="card card-dark">
    <div class="card-header">
        <h3 class="card-title"><i class="fa fa-redo-alt mr-2"></i>AutoPay</h3>
    </div>

    <div class="card-body">
            <!-- Setup pt1: Stripe ID not found / auto-payment not configured -->
            <?php if (!$stripe_client_details || empty($stripe_id)) { ?>

                <b>Save card details</b><br>
                In order to set up automatic payments, you must create a customer record in Stripe.<br>
                First, you must authorize Stripe to store your card details for the purpose of automatic payment.
            <br><br>

                <div class="col-5">
                    <form action="post.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="client_id" value="<?= $client_id ?>">

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="consent" name="consent" value="1" required>
                                <label for="consent" class="custom-control-label">
                                    I grant consent for automatic payments
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="form-control btn-success" name="create_stripe_customer">Create Stripe Customer Record</button>
                        </div>
                    </form>
                </div>

            <?php }

            // Setup pt2: Stripe ID found / payment may be configured -->
            elseif (empty($stripe_pm)) { ?>

                <b>Save card details</b><br>
                Please add the payment details you would like to save.<br>
                By adding payment details here, you grant consent for future automatic payments of invoices.<br><br>

                <?php if ($checkout_error) { ?>
                    <div class="alert alert-danger">Could not initialize payment setup: <?= $checkout_error ?></div>
                <?php } elseif ($checkout_client_secret) { ?>
                    <div id="checkout"></div>
                    <script src="https://js.stripe.com/v3/"></script>
                    <script>
                    (async function() {
                        try {
                            const stripe = Stripe(<?= json_encode($config_stripe_publishable) ?>);
                            const checkout = await stripe.initEmbeddedCheckout({
                                fetchClientSecret: async () => <?= json_encode($checkout_client_secret) ?>,
                            });
                            checkout.mount('#checkout');
                        } catch (err) {
                            console.error('Stripe initEmbeddedCheckout error:', err);
                            const div = document.getElementById('checkout');
                            if (div) {
                                div.innerHTML = '<div class="alert alert-danger">Stripe checkout error: ' + (err.message || String(err)) + '</div>';
                            }
                        }
                    })();
                    </script>
                <?php } else { ?>
                    <div class="alert alert-danger">Payment setup unavailable. Please check Stripe configuration in Admin &rarr; Payment Providers.</div>
                <?php } ?>

            <?php }

            // Manage the saved card
            else { ?>

                <b>Manage saved payment methods</b>

                <?php
                $stripe_pm_description = escapeHtml($stripe_client_details['stripe_pm_description'] ?? $stripe_pm);
                ?>

                <ul><li><?= $stripe_pm_description ?></li></ul>

                <hr>
                <b>Actions</b><br>
                - <a href="post.php?stripe_remove_pm&client_id=<?= $client_id ?>&pm=<?= $stripe_pm ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>">Remove saved payment method</a>

            <?php } ?>


        </div>

    </div>
</div>

<?php

require_once "../includes/footer.php";
