
<?php

session_start(); // load session

require_once "../controllers/ArtworkController.php";

$controller = new ArtworkController(); // to use the show($id) function

?>

<!DOCTYPE html>
<html>

<head>
    <title>Shopping Cart</title>
</head>

<body>

    <h1>Shopping Cart</h1>

    <pre>
        <?php

            $total = 0;

            if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {

                echo "<p>Your cart is empty.</p>";

            }
            else {

                //loops the each key:value pair in the cart grabbing the id and quantity
                foreach ($_SESSION['cart'] as $productId => $quantity) {
                    
                    $product = $controller->show($productId); //stores the results
                    $row = $product->fetch(PDO::FETCH_ASSOC); //uses $product object to grab results of the query and converts to assoc

                    echo "<p>";
                    echo "Title: " . $row['Title'];
                    echo "<br>";
                    echo "Price: $" . $row['Price'];
                    echo "<br>";
                    echo "Quantity: " . $quantity;
                    echo "</p>";

                    // calculates the total accumulation
                    $subtotal = $row['Price'] * $quantity;
                    echo "Subtotal: $" . number_format($subtotal, 2);
                    $total += $subtotal;
                }

                    //displays it
                    echo "<br><br>";
                    echo "<h3>Cart Total: $" . number_format($total, 2) . "</h3>";
            }  
        ?>

        <!-- Checkout Button for the cart -->
        <br>
        <a href="checkout.php">
            Proceed To Checkout
        </a>

    </pre>

</body>

</html>