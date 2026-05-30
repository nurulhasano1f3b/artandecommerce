<?php

require_once "../models/Customer.php";

$customer = new Customer();

$customerId = $customer->createCustomer(
    "test@example.com",
    "John",
    "Smith",
    "Mr.",
    "123 Test Street",
    "Darwin",
    "NT",
    "Australia",
    "0800",
    "0412345678"
);

echo "Customer Created Successfully<br>";
echo "CustomerID: " . $customerId;