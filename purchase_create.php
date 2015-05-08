<?php

	require_once 'apiPurchases.php';

	//only accepts GET requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "GET") {

		//CHECK ALL FIELDS ARE FILLED OUT
		//check that the inputs that are string/int are not empty 
		//list of the required input fields in the form
		$requiredInput = array('book_id', 'user');
		foreach($requiredInput as $input) {
	  		if (empty($_GET[$input])) {
	    		echo json_encode(array("success" => False, "message" => "The request sent contained empty fields."));
	    		exit;
	  		}
		}

		//assign form input to a variable 
		$bookId = $_GET['book_id'];
		$username = $_GET['user'];

		//run the createPurchase method which is in apiPurchases
		$response = createPurchase($bookId, $username);

		echo json_encode($response);
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a GET request."));
	}

?>