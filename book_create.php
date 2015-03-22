<?php

	require_once 'apiBooks.php';

	session_start();

	//only accepts post requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "POST") {

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