<?php

require_once "../models/PurchaseItem.php";

$item = new PurchaseItem();

$item->createPurchaseItem(
    2,
    1,
    3
);

echo "Purchase Item Created Successfully";

?>