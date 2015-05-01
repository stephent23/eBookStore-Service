<?php

	require_once 'apiBooks.php';

	//only accepts GET requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "GET") {
		//initialise variables
		$title = "";
		$authors = "";
		$start = "";
		$length = "";

		//assign form input to a variable 
		if (isset($_GET['title'])) {
			$title = $_GET['title'];
		}
		if(isset($_GET['authors'])) {
			$authors = $_GET['authors'];
		}
		if (isset($_GET['start'])) {
			$start = $_GET['start'];
		}
		if(isset($_GET['end']))	{
			$length = $_GET['length'];
		}

		//run the getBooks method which is in apiBooks
		$response = getBooks($title, $authors, $start, $length);
		echo json_encode($response);
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a POST request."));
	}

?>