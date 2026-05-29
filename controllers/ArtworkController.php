<?php

require_once "../models/Artwork.php";

class ArtworkController {
    
    public function index(){
        
        $artwork = new Artwork(); //creates the model

        $result = $artwork->getAllArtworks(); // runs the "getAllArtworks" function to run the SQL query

        return $result; // returns the result variable that stores the products from the query
    }

    public function show($id) {

        $artwork = new Artwork();

        $result = $artwork->getArtworkByID($id);

        return $result;
    }
}
?>