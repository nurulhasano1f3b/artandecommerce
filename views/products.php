<?php
require_once "../config/security_headers.php"; // M-04
require_once "../config/session.php";           // M-02
require_once "../controllers/ArtworkController.php";

$controller = new ArtworkController();
$products   = $controller->index();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
</head>
<body>

    <h1>Artwork Products</h1>

    <?php while ($row = $products->fetch(PDO::FETCH_ASSOC)): ?>
    <div>
        <h3>
            <a href="productDetails.php?id=<?php echo (int)$row['ProductID']; ?>">
                <?php echo htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </h3>
        <p><?php echo htmlspecialchars($row['Description'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p>$<?php echo htmlspecialchars($row['Price'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p><?php echo htmlspecialchars($row['Category'], ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <hr>
    <?php endwhile; ?>

</body>
</html>
