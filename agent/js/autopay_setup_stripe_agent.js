// Agent-side autopay card setup — uses server-embedded clientSecret (no AJAX needed)
const stripe = Stripe(document.getElementById("stripe_publishable_key").value);

stripe.initEmbeddedCheckout({
    fetchClientSecret: () => Promise.resolve(stripeCheckoutClientSecret),
}).then(checkout => checkout.mount('#checkout'));
