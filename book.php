<?php

	require_once 'apiBooks.php';

	//only accepts GET requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "GET") {
		//assign form input to a variable 
		$bookId = $_GET['book_id'];

		//run the getImage method which is in apiBooks
		$response = getBook($bookId);

		echo json_encode($response);

	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a GET request."));
	}

?>