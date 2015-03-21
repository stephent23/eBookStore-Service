<?php

	require_once 'apiBooks.php';

	session_start();

	//only accepts post requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "POST") {

		//list of the required input fields in the form
		$requiredInput = array('title', 'authors', 'description', 'price');

		//check to see if any of them are blank/empty
		foreach($requiredInput as $input) {
	  		if (empty($_POST[$input])) {
	    		echo json_encode(array("success" => False, "message" => "The request sent contained empty fields."));
	    		exit;
	  		}
		}

		//check whether the file upload fields are empty
		if($_FILES["image"]["error"] == 4) {
			echo json_encode(array("success" => False, "message" => "The image field cannot be left empty."));
			exit;
		}
		if($_FILES["content"]["error"] == 4) {
			echo json_encode(array("success" => False, "message" => "The content field cannot be left empty."));
			exit;
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