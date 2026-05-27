<?php

// test file - checking to see if the db connects with no issues

/*
Browser --> database.php --> PDO --> MySQL --> ecommerce_db
*/

// loads the db class
require_once "../config/database.php";

// creates the db object
$database = new Database();

// connects to mysql
$conn = $database->connect();

// self-debug to see if it worked
if($conn){
    echo "Database connected successfully!";
}
else{
    echo "Database connection failed.";
}

?>