<?php

	require_once 'apiPurchases.php';

	//only accepts GET requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "GET") {
		//initialise variables
		$user = "";
		$bookId = "";
		$start = "";
		$length = "";

		//assign form input to a variable 
		if (isset($_GET['user'])) {
			$user = $_GET['user'];
		}
		if(isset($_GET['book_id'])) {
			$bookId = $_GET['book_id'];
		}
		if (isset($_GET['start'])) {
			$start = $_GET['start'];
		}
		if(isset($_GET['length']))	{
			$length = $_GET['length'];
		}

		//run the getPurchases method which is in apiPurchases
		$response = getPurchases($user, $bookId, $start, $length);
		echo json_encode($response);
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a GET request."));
	}

?>