

<?php

class Database {

    private $host = "localhost";
    private $db_name = "ecommerce_db";
    private $username = "root";
    private $password = "";

    public $conn;

    public function connect() { // opens db connection

        $this->conn = null;

        try { // error handling

            $this->conn = new PDO( //php data objects
                "mysql:host=".$this->host.";dbname=".$this->db_name,
                $this->username,
                $this->password
            );

            $this->conn->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

        } catch(PDOException $e){ // catches errors

            echo "Connection Error: " . $e->getMessage();

        }

        return $this->conn;
    }
}
?>