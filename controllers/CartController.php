
<?php

class CartController {

    public function addToCart($productId) {

        // if the cart doesnt exist yet creates an empty one
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // checks if cart exists and if product exists
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]++; //increments quantity of productid that exists within the cart
        }
        else {
            $_SESSION['cart'][$productId] = 1; //else creates a new key-value pair
        }

    }

}

?>