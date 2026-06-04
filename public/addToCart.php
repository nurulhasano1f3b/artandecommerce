<?php
require_once "../config/session.php";           // M-02 (replaces session_start())
require_once "../controllers/CartController.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['productId'])) {
    http_response_code(400);
    exit("Invalid request.");
}

// L-02: Reject anything that is not a positive integer
$productId = filter_var($_POST['productId'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$productId) {
    http_response_code(400);
    exit("Invalid product.");
}

// M-03: Regenerate session ID when the first item is added to a fresh session
$cartWasEmpty = empty($_SESSION['cart']);

$cart = new CartController();
$cart->addToCart($productId);

if ($cartWasEmpty) {
    session_regenerate_id(true); // fixes session fixation on cart creation
}

// C-01: PRG pattern — redirect instead of echoing session state
header("Location: ../views/productDetails.php?id=" . $productId);
exit();
