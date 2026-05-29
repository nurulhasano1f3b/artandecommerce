
<?php

require_once "../controllers/ArtworkController.php"; // grabs artworkcontroller

$controller = new ArtworkController(); //creates an object of artwork controller

$products = $controller->index(); //calls the controller object to grab the products

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Products</title>
</head>

<body>
    
    <h1>Artwork Products</h1>

    <?php while ($row = $products->fetch(PDO::FETCH_ASSOC)): ?> <!-- loops through the db to build the page -->

    <div>
        <!-- each gets dynamically filled by the results of the loop above -->
        <h3>
            <a href = "productDetails.php?id=<?php echo $row['ProductID']; ?>">
                <?php echo $row['Title']; ?>
            </a>
        </h3> 

        <p><?php echo $row['Description']; ?></p>

        <p>$<?php echo $row['Price']; ?></p>

        <?php echo $row['Category']; ?>
        
    </div>

    <hr>

    <?php endwhile; ?>

</body>
</html>