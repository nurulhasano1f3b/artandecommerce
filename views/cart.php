<?php
require_once "../config/security_headers.php"; // M-04
require_once "../config/session.php";           // M-02 (replaces session_start())
require_once "../controllers/ArtworkController.php";

$controller = new ArtworkController();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
</head>
<body>

    <h1>Shopping Cart</h1>

    <?php if (empty($_SESSION['cart'])): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <?php
        $total = 0;
        foreach ($_SESSION['cart'] as $productId => $quantity):
            $product = $controller->show((int)$productId);
            $row     = $product->fetch(PDO::FETCH_ASSOC);
            if (!$row) continue;
            $subtotal = $row['Price'] * $quantity;
            $total   += $subtotal;
        ?>
        <div>
            <p><strong><?php echo htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <p>Price: $<?php echo htmlspecialchars($row['Price'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Quantity: <?php echo (int)$quantity; ?></p>
            <p>Subtotal: $<?php echo number_format($subtotal, 2); ?></p>
        </div>
        <hr>
        <?php endforeach; ?>
        <h3>Cart Total: $<?php echo number_format($total, 2); ?></h3>
    <?php endif; ?>

    <br>
    <a href="checkout.php">Proceed To Checkout</a>

</body>
</html>
