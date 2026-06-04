<?php
require_once "../config/security_headers.php"; // M-04
require_once "../config/session.php";           // M-02
require_once "../controllers/ArtworkController.php";

// L-02: Validate id is a positive integer before querying
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(404);
    exit("Product not found.");
}

$controller = new ArtworkController();
$product    = $controller->show($id);
$row        = $product->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    exit("Product not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>

    <h1><?php echo htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8'); ?></h1>

    <p><?php echo htmlspecialchars($row['Description'], ENT_QUOTES, 'UTF-8'); ?></p>

    <p>Price: $<?php echo htmlspecialchars($row['Price'], ENT_QUOTES, 'UTF-8'); ?></p>

    <p>Category: <?php echo htmlspecialchars($row['Category'], ENT_QUOTES, 'UTF-8'); ?></p>

    <p>Image: <?php echo htmlspecialchars($row['Image'], ENT_QUOTES, 'UTF-8'); ?></p>

    <form method="POST" action="../public/addToCart.php">
        <input type="hidden" name="productId" value="<?php echo (int)$row['ProductID']; ?>">
        <button type="submit">Add To Cart</button>
    </form>

</body>
</html>
