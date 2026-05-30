<?php

require_once "../controllers/ArtworkController.php";

$controller = new ArtworkController();

$product = $controller->recent();

$row = $product->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Art Site</title>
</head>

<body>
    
    <h1>Welcome to Art Site!</h1>

    <div>
        <h3>
            <a href = "products.php">
                Products
            </a>
        </h3> 

        <p>See a list of all our available products</p>        
    </div>

    <div>
        <h3>
            <a href = "cart.php">
                Cart
            </a>
        </h3> 

        <p>View the items in your cart</p>        
    </div>

    <hr>

    <div>
        <h2>
            <a>
                Recent Addition
            </a>
        </h2>
        <h3>
            <a href = "productDetails.php?id=<?php echo $row['ProductID']; ?>">
                <?php echo $row['Title']; ?>
            </a>
        </h3> 

        <p><?php echo $row['Description']; ?></p>

        <p>$<?php echo $row['Price']; ?></p>

        <p><?php echo $row['Category']; ?></p>
    </div>

    <hr>

</body>
</html>