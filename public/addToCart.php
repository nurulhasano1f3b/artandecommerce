

<?php

session_start(); // stararts a new session or loads the session. so functions work properly before hitting the controller

require_once "../controllers/CartController.php";

// checks if post data exists/given from productdetails/form
if(isset($_POST['productId'])) {

    $productId = $_POST['productId']; //stores the given productid inside the variable

    $cart = new CartController(); //create the cartcontroller object

    // uses the cartcontroller method to addtocart based on the productid and updates
    $cart->addToCart($productId);

    

    // uncomment the below out later, its the redirect. Code below it shows cart functionality

    /*
    // redirects user to ensure no duplicate submission (PRG)
    header("Location: ../views/productDetails.php?id=$productId");
    exit();
    */

    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";

}

?>