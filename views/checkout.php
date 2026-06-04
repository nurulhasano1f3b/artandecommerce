<?php
require_once "../config/security_headers.php"; // M-04
require_once "../config/session.php";           // M-02 (replaces session_start())

// C-03: Generate CSRF token once per session visit to this page
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
</head>
<body>

    <h1>Checkout</h1>

    <form method="POST" action="../public/processCheckout.php">

        <!-- C-03: CSRF synchroniser token -->
        <input type="hidden" name="csrf_token"
               value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

        <label>Email:</label>
        <input type="email" name="email" required maxlength="255"><br><br>

        <label>First Name:</label>
        <input type="text" name="firstName" required maxlength="50"><br><br>

        <label>Last Name:</label>
        <input type="text" name="lastName" required maxlength="50"><br><br>

        <label>Title:</label><br>
        <select name="title">
            <option value="Mr.">Mr.</option>
            <option value="Mrs.">Mrs.</option>
            <option value="Ms.">Ms.</option>
            <option value="Mx.">Mx.</option><!-- L-05: was "Mx" — fixed to match ENUM -->
        </select><br><br>

        <label>Address:</label><br>
        <input type="text" name="address" required maxlength="75"><br><br>

        <label>City:</label><br>
        <input type="text" name="city" required maxlength="50"><br><br>

        <label>State:</label><br>
        <input type="text" name="state" required maxlength="50"><br><br>

        <label>Country:</label><br>
        <input type="text" name="country" required maxlength="50"><br><br>

        <label>Post Code:</label><br>
        <input type="text" name="postcode" required maxlength="10"><br><br>

        <label>Phone:</label><br>
        <input type="text" name="phone" required maxlength="15"><br><br>

        <button type="submit">Place Order</button>

    </form>

</body>
</html>
