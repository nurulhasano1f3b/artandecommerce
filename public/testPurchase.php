<?php

require_once "../models/Purchase.php";

$purchase = new Purchase();

$purchaseId = $purchase->createPurchase(2);

echo "Purchase Created Successfully<br>";
echo "PurchaseID: " . $purchaseId;

?>