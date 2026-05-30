

<?php

require_once "../config/database.php";

class Purchase {

    private $conn;
    private $table = "Purchases";

    public function __construct() {

        $database = new Database();

        $this->conn = $database->connect();

    }

    //function to insert new data and return purchaseid
    public function createPurchase($customerId) {

    $query = "INSERT INTO " . $this->table . "
    (
        PurchaseDate,
        CustomerID
    )
    VALUES
    (
        NOW(),
        ?
    )";

    $stmt = $this->conn->prepare($query);

    $stmt->execute([
        $customerId
    ]);

    // returns the purchaseid
    return $this->conn->lastInsertId();

}

}

?>