// Agent-side autopay card setup — client_secret embedded server-side, no AJAX needed
const stripe = Stripe(document.getElementById("stripe_publishable_key").value);

initialize();

async function initialize() {
    try {
        const checkout = await stripe.initEmbeddedCheckout({
            fetchClientSecret: async () => window.stripeCheckoutClientSecret,
        });
        checkout.mount('#checkout');
    } catch (err) {
        const div = document.getElementById('checkout');
        if (div) {
            div.innerHTML = '<div class="alert alert-danger">Stripe checkout error: ' + err.message + '</div>';
        }
        console.error('Stripe initEmbeddedCheckout error:', err);
    }
}
