
<?php

class CartController {

    public function addToCart($productId) {

        // if the cart doesnt exist yet creates an empty one
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // L-03: Cap quantity at 99 to prevent unbounded session bloat
        if (isset($_SESSION['cart'][$productId])) {
            if ($_SESSION['cart'][$productId] < 99) {
                $_SESSION['cart'][$productId]++;
            }
        } else {
            $_SESSION['cart'][$productId] = 1;
        }

    }

}

?>