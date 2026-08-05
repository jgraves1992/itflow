<?php

require_once "includes/inc_all_client.php";

// Perms
enforceUserPermission('module_sales');

// Initialize stripe
require_once '../includes/stripe_init.php';

// Get Stripe vars (moved to payment_providers table in 26.08)
$stripe_vars = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT payment_provider_active, payment_provider_public_key, payment_provider_private_key FROM payment_providers LIMIT 1"));
$config_stripe_enable = $stripe_vars ? intval($stripe_vars['payment_provider_active']) : 0;
$config_stripe_publishable = $stripe_vars ? escapeHtml($stripe_vars['payment_provider_public_key']) : '';
$config_stripe_secret = $stripe_vars ? escapeHtml($stripe_vars['payment_provider_private_key']) : '';

// Get client's Stripe relationship (client_payment_provider) and saved PM (client_saved_payment_methods)
$stripe_client_details = mysqli_fetch_assoc(mysqli_query($mysqli, "
    SELECT cpp.payment_provider_client AS stripe_id,
           spm.saved_payment_provider_method AS stripe_pm
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

                <input type="hidden" id="stripe_publishable_key" value="<?= $config_stripe_publishable ?>">
                <input type="hidden" id="autopay_client_id" value="<?= $client_id ?>">
                <script src="https://js.stripe.com/v3/"></script>
                <script src="js/autopay_setup_stripe_agent.js"></script>
                <div id="checkout">
                    <!-- Checkout will insert the payment form here -->
                </div>

            <?php }

            // Manage the saved card
            else { ?>

                <b>Manage saved payment methods</b>

                <?php

                try {
                    // Initialize
                    $stripe = new \Stripe\StripeClient($config_stripe_secret);

                    // Get payment method info (last 4 digits etc)
                    $payment_method = $stripe->customers->retrievePaymentMethod(
                        $stripe_id,
                        $stripe_pm,
                        []
                    );

                } catch (Exception $e) {
                    $error = $e->getMessage();
                    error_log("Stripe payment error - encountered exception when fetching payment method info for $stripe_pm: $error");
                    logApp("Stripe", "error", "Exception when fetching payment method info for $stripe_pm: $error");
                }

                $card_name = escapeHtml($payment_method->billing_details->name);
                $card_brand = escapeHtml($payment_method->card->display_brand);
                $card_last4 = escapeHtml($payment_method->card->last4);
                $card_expires = escapeHtml($payment_method->card->exp_month) . "/" . escapeHtml($payment_method->card->exp_year);

                ?>

                <ul><li><?= "$card_name - $card_brand card ending in $card_last4, expires $card_expires" ?></li></ul>

                <hr>
                <b>Actions</b><br>
                - <a href="post.php?stripe_remove_pm&client_id=<?= $client_id ?>&pm=<?= $stripe_pm ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>">Remove saved payment method</a>

            <?php } ?>


        </div>

    </div>
</div>

<?php

require_once "../includes/footer.php";
