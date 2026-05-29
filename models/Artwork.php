<?php

require_once "../config/database.php";

class Artwork {

    private $conn;
    private $table = "Products"; // easier to maintain

    // function runs when new artwork gets created
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect(); // when artwork exists connect to db

    }

    // function to grab all artworks
    public function getAllArtworks() {
        $query = "SELECT * FROM " . $this->table; // grab all products from the db

        $stmt = $this->conn->prepare($query); //prepared statement protects from sql injection

        $stmt->execute();

        return $stmt;
    }


    // function to grab the product details
    public function getArtworkByID($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE ProductID = ?";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([$id]); //prepared statement

        return $stmt;
    }
}

?>