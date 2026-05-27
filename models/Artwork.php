<?php

require_once "../config/database.php";

class Artwork {

    private $conn;
    private $table = "Products";

    // function runs when new artwork gets created
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect(); // when artwork exists connect to db

    }

    public function getAllArtworks() {
        $query = "SELECT * FROM " . $this->table; // grab all products from the db

        $stmt = $this->conn->prepare($query); //prepared statement protects from sql injection

        $stmt->execute();

        return $stmt;
    }
}

?>