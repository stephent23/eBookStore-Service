<?php

	require_once 'apiPurchases.php';

	//only accepts GET requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "GET") {

		if(isset($_GET['success'])) {
			if($_GET['success'] == "false") {
				cancelPurchase($_GET['token'], $_GET['username'], $_GET['book_id']);
			}
		}
		else {
			//CHECK ALL FIELDS ARE FILLED OUT
			//check that the inputs that are string/int are not empty 
			//list of the required input fields in the form
			$requiredInput = array('book_id', 'user', 'token');
			foreach($requiredInput as $input) {
		  		if (empty($_GET[$input])) {
		    		echo json_encode(array("success" => False, "message" => "The request sent contained empty fields."));
		    		exit;
		  		}
			}

			//assign form input to a variable 
			$bookId = $_GET['book_id'];
			$username = $_GET['user'];
			$token = $_GET['token'];

			//run the createPurchase method which is in apiPurchases
			cancelPurchase($token, $username, $bookId);
		}
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a GET request."));
	}

?>