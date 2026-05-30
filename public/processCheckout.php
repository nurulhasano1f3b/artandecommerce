

<?php

session_start();

require_once "../models/Customer.php";
require_once "../models/Purchase.php";
require_once "../models/PurchaseItem.php";

// models
$customerModel = new Customer();
$purchaseModel = new Purchase();
$purchaseItemModel = new PurchaseItem();

echo "Models Loaded Successfully"; //here for debug purposes

// post data from form

$email = $_POST['email'];
$firstName = $_POST['firstName'];
$lastName = $_POST['lastName'];
$title = $_POST['title'];
$address = $_POST['address'];
$city = $_POST['city'];
$state = $_POST['state'];
$country = $_POST['country'];
$postcode = $_POST['postcode'];
$phone = $_POST['phone'];

echo "POST Data Retrieved";//debug


// customer creation in db + returns the customderid
$customerId = $customerModel->createCustomer(
    $email,
    $firstName,
    $lastName,
    $title,
    $address,
    $city,
    $state,
    $country,
    $postcode,
    $phone
);

echo "<br>";
echo "Customer Created: " . $customerId;//debug line


// purchase creation in db for purchaseid
// only need to give the customerid, since date is done using MySQL
$purchaseId = $purchaseModel->createPurchase(
    $customerId
);

echo "<br>";
echo "Purchase Created: " . $purchaseId; // debug line


// for loop to iterate the cart to send the purchaseid, productid, quantity per iteration
foreach ($_SESSION['cart'] as $productId => $quantity) {

    $purchaseItemModel->createPurchaseItem(
        $purchaseId,
        $productId,
        $quantity
    );

}

echo "<br>";
echo "Purchase Items Created"; //debug line


// order completion

// empties the cart
unset($_SESSION['cart']);

echo "<br><br>";
echo "<h1>Order Successfully Placed!</h1>";
echo "<p>Your Purchase ID is: " . $purchaseId . "</p>";


?>