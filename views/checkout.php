<?php
session_start();
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

        <!-- Customer deets -->
        <label>Email:</label>
        <input type="email" name = "email" required><br><br>

        <label>First Name:</label>
        <input type = "text" name = "firstName" required><br><br>

        <label>Last Name:</label>
        <input type = "text" name = "lastName" required><br><br>

        <label>Title:</label><br>
        <select name = "title">
            <option>Mr.</option>
            <option>Mrs.</option>
            <option>Ms.</option>
            <option>Mx</option> <!-- gender neutral option -->
        </select><br><br>

        <!-- Location deetz -->
        <label>Address:</label><br>
        <input type="text" name="address" required><br><br>

        <label>City:</label><br>
        <input type="text" name="city" required><br><br>

        <label>State:</label><br>
        <input type="text" name="state" required><br><br>

        <label>Country:</label><br>
        <input type="text" name="country" required><br><br>

        <label>PostCode:</label><br>
        <input type="text" name="postcode" required><br><br>

        <label>Phone:</label><br>
        <input type="text" name="phone" required><br><br>

        <!-- Button to submit -->
        <button type="submit">
            Place Order
        </button>

    </form>

</body>

</html>