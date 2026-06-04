<?php
require_once "../config/security_headers.php"; // M-04
require_once "../config/session.php";           // M-02
require_once "../controllers/ArtworkController.php";

$controller = new ArtworkController();
$product    = $controller->recent();
$row        = $product->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Art Site</title>
</head>
<body>

    <h1>Welcome to Art Site!</h1>

    <div>
        <h3><a href="products.php">Products</a></h3>
        <p>See a list of all our available products</p>
    </div>

    <div>
        <h3><a href="cart.php">Cart</a></h3>
        <p>View the items in your cart</p>
    </div>

    <hr>

    <?php if ($row): ?>
    <div>
        <h2>Recent Addition</h2>
        <h3>
            <a href="productDetails.php?id=<?php echo (int)$row['ProductID']; ?>">
                <?php echo htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </h3>
        <p><?php echo htmlspecialchars($row['Description'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p>$<?php echo htmlspecialchars($row['Price'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p><?php echo htmlspecialchars($row['Category'], ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <?php endif; ?>

    <hr>

</body>
</html>
