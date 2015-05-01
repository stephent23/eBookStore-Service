<?php

	require_once 'apiBooks.php';

	//only accepts post requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "POST") {

		//CHECK ALL FIELDS ARE FILLED OUT
		//check that the inputs that are string/int are not empty 
		//list of the required input fields in the form
		$requiredInput = array('title', 'authors', 'description', 'price');
		foreach($requiredInput as $input) {
	  		if (empty($_POST[$input])) {
	    		echo json_encode(array("success" => False, "message" => "The request sent contained empty fields."));
	  		}
		}

		//assign each form input to a variable 
		$title = $_POST['title'];
		$authors = $_POST['authors'];
		$description = $_POST['description'];
		$price = $_POST['price'];
		$image = $_FILES['image'];
		$content = $_FILES['content'];

		//run the createBook method which is in apiBooks
		$response = createBook($title, $authors, $description, $price, $image, $content);

		echo json_encode($response);
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a POST request."));
	}

?>