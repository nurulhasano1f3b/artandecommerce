<?php

require_once "../controllers/ArtworkController.php";

$controller = new ArtworkController();

// grabs id from url
$id = $_GET['id'];

$product = $controller->show($id);

// singular row
$row = $product->fetch(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html>

<head>
    <title><?php echo $row['Title']; ?></title>
</head>

<body>

    <h1><?php echo $row['Title']; ?></h1>

    <p><?php echo $row['Description']; ?></p>

    <p>Price: $<?php echo $row['Price']; ?></p>

    <p>Category: <?php echo $row['Category']; ?></p>

    <p>Image: <?php echo $row['Image']; ?></p>




    <!-- Add to Cart button -->

    <form method="POST" action="../public/addToCart.php">
        <input
            type="hidden"
            name="productId"
            value="<?php echo $row['ProductID']; ?>"
        >

        <button type="submit">
            Add To Cart
        </button>
    </form>

</body>

</html>