<?php

require_once "../config/database.php";

class PurchaseItem {

    private $conn;
    private $table = "PurchaseItem";

    public function __construct() {

        $database = new Database();

        $this->conn = $database->connect();

    }

    //function to insert the purchaseid, productid, quantity
    public function createPurchaseItem(
        $purchaseId,
        $productId,
        $quantity
        ) {

    $query = "INSERT INTO " . $this->table . "
    (
        PurchaseID,
        ProductID,
        Quantity
    )
    VALUES
    (
        ?,
        ?,
        ?
    )";

    $stmt = $this->conn->prepare($query);

    $stmt->execute([
        $purchaseId,
        $productId,
        $quantity
    ]);

    }

}

?>