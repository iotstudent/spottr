<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<script src="https://checkout.flutterwave.com/v3.js"></script>
<button type="button" onclick="makePayment()">Pay Now</button>

<script>
function makePayment() {
    FlutterwaveCheckout({
        public_key: "FLWPUBK_TEST-06545f3c5568673c6310c95a3d039d44-X",
        tx_ref: "spottr-tx-" + Math.floor((Math.random() * 1000000000) + 1),
        amount: 1000,
        currency: "NGN",
        payment_options: "card, ussd",
        redirect_url: "http://127.0.0.1:8000/api/v1/verify-fiat-payment",
        meta: {
            consumer_id: 23,
            consumer_mac: "92a3-912ba-1192a",
        },
        customer: {
            email: "user@example.com",
            phone_number: "08012345678",
            name: "John Doe",
        },
        customizations: {
            title: "My Awesome Product",
            description: "Payment for items in cart",
            // logo: "https://yourwebsite.com/logo.png",
        },
    });
}
</script>


</body>
</html>
