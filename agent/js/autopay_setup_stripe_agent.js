// Agent-side autopay card setup — mirrors autopay_setup_stripe.js but posts to agent/post.php
const stripe   = Stripe(document.getElementById("stripe_publishable_key").value);
const clientId = document.getElementById("autopay_client_id").value;

initialize();

async function initialize() {
    const fetchClientSecret = async () => {
        const response = await fetch("/agent/post.php?create_stripe_checkout&client_id=" + clientId, {
            method: "POST",
        });
        const { clientSecret } = await response.json();
        return clientSecret;
    };

    const checkout = await stripe.initEmbeddedCheckout({
        fetchClientSecret,
    });

    checkout.mount('#checkout');
}
