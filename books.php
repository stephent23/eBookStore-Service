<?php

	require_once 'apiBooks.php';

	//only accepts GET requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "GET") {
		//assign form input to a variable 
		$title = $_GET['title'];
		$authors = $_GET['authors'];
		$start = $_GET['start'];
		$length = $_GET['length'];

		//run the getBooks method which is in apiBooks
		$response = getBooks($title, $authors, $start, $length);
		echo json_encode($response);

	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a POST request."));
	}

?>