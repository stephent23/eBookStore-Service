<?php

	require_once 'apiPurchases.php';

	//only accepts GET requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "GET") {
		//check if get resquest is from paypal redirect
		if($_GET['success'] == "true") {
			echo json_encode(array("success" => True, "message" => "Payment successful"));
		}
		elseif($_GET['success'] == "false") {
			echo json_encode(array("success" => False, "message" => "Payment Cancelled"));
		}
		else {
			//assign form input to a variable 
			$bookId = $_GET['book_id'];
			$username = $_GET['username'];

			//run the createPurchase method which is in apiPurchases
			$response = createPurchase($bookId, $username);

			echo json_encode($response);
		}
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a GET request."));
	}

?>