


<?php

require_once "../config/database.php";

class Customer {

    private $conn;
    private $table = "Customers";

    public function __construct() {

        $database = new Database();

        $this->conn = $database->connect();

    }


    // function to create the customer after checkout form is submitted
    public function createCustomer(
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
    ) {

        $query = "INSERT INTO " . $this->table . "
        (
            Email,
            FirstName,
            LastName,
            Title,
            Address,
            City,
            State,
            Country,
            PostCode,
            Phone
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?,?,?
        )";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
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
        ]);
        
        // after db creates a customerid it returns it to caller
        return $this->conn->lastInsertID();

    }

}
?>