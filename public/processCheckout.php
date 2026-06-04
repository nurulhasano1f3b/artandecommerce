<?php
require_once "../config/security_headers.php"; // M-04
require_once "../config/session.php";           // M-02

// H-06: Abort before any DB work if the cart is empty
if (empty($_SESSION['cart'])) {
    http_response_code(400);
    exit("Your cart is empty.");
}

// Only accept POST submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method not allowed.");
}

// C-03: Verify CSRF token — reject forged cross-site requests
if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit("Invalid request.");
}
unset($_SESSION['csrf_token']);

// H-05: Server-side validation — HTML `required` is bypassable with curl
$email     = trim($_POST['email']     ?? '');
$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName']  ?? '');
$title     = trim($_POST['title']     ?? '');
$address   = trim($_POST['address']   ?? '');
$city      = trim($_POST['city']      ?? '');
$state     = trim($_POST['state']     ?? '');
$country   = trim($_POST['country']   ?? '');
$postcode  = trim($_POST['postcode']  ?? '');
$phone     = trim($_POST['phone']     ?? '');

$errors = [];

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    $errors[] = "A valid email address is required.";
}
if ($firstName === '' || strlen($firstName) > 50) {
    $errors[] = "First name is required (max 50 characters).";
}
if ($lastName === '' || strlen($lastName) > 50) {
    $errors[] = "Last name is required (max 50 characters).";
}
$validTitles = ['Mr.', 'Mrs.', 'Mx.', 'Ms.'];
if (!in_array($title, $validTitles, true)) {
    $errors[] = "Invalid title selected.";
}
if ($address === '' || strlen($address) > 75) {
    $errors[] = "Address is required (max 75 characters).";
}
if ($city === '' || strlen($city) > 50) {
    $errors[] = "City is required (max 50 characters).";
}
if ($state === '' || strlen($state) > 50) {
    $errors[] = "State is required (max 50 characters).";
}
if ($country === '' || strlen($country) > 50) {
    $errors[] = "Country is required (max 50 characters).";
}
if ($postcode === '' || strlen($postcode) > 10) {
    $errors[] = "Post code is required (max 10 characters).";
}
if ($phone === '' || strlen($phone) > 15) {
    $errors[] = "Phone is required (max 15 characters).";
}

// Validate cart integrity — each key must be a positive integer, quantity between 1-99
foreach ($_SESSION['cart'] as $productId => $quantity) {
    if (!ctype_digit((string)$productId) || (int)$quantity < 1 || (int)$quantity > 99) {
        $errors[] = "Invalid cart data.";
        break;
    }
}

if (!empty($errors)) {
    http_response_code(400);
    // Errors are plain strings we composed — htmlspecialchars is a safety net
    exit(implode(' ', array_map('htmlspecialchars', $errors)));
}

require_once "../models/Customer.php";
require_once "../models/Purchase.php";
require_once "../models/PurchaseItem.php";
require_once "../services/NotificationService.php";

$customerModel     = new Customer();
$purchaseModel     = new Purchase();
$purchaseItemModel = new PurchaseItem();

$customerId = $customerModel->createCustomer(
    $email, $firstName, $lastName, $title,
    $address, $city, $state, $country, $postcode, $phone
);

$purchaseId = $purchaseModel->createPurchase($customerId);

foreach ($_SESSION['cart'] as $productId => $quantity) {
    $purchaseItemModel->createPurchaseItem($purchaseId, (int)$productId, (int)$quantity);
}

// Fire order notification and persist customer identity in the session
$notificationService = new NotificationService();
$notificationService->notifyOrderPlaced($customerId, $purchaseId);
$_SESSION['customer_id'] = $customerId;

unset($_SESSION['cart']);

// M-03: Regenerate session ID after the privilege change (guest → identified customer)
session_regenerate_id(true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed</title>
</head>
<body>
    <h1>Order Successfully Placed!</h1>
    <p>Your Purchase ID is: <?php echo (int)$purchaseId; ?></p>
    <p><a href="../views/home.php">Return to home</a></p>
</body>
</html>
