<?php

	require_once 'apiPurchases.php';

	//only accepts GET requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "GET") {
		//Check if redirect from PayPal
		if(isset($_GET['success'])) {
			if($_GET['success'] == "true") {
				$purchaseInformation = getPurchaseInfo($_GET['paymentId']);
				$response = activatePurchase($purchaseInformation['book'], $purchaseInformation['username'], $_GET['token'], $_GET['PayerID']);
				echo json_encode($response);	
			}
			elseif($_GET['success'] == "false") {
				echo json_encode(array("success" => False, "message" => "Payment Cancelled"));
			}
		}
		else {
			//manual input through a form
			//assign form input to a variable 
			$bookId = $_GET['book_id'];
			$username = $_GET['user'];
			$token = $_GET['token'];
			$PayerID = $_GET['PayerID'];

			//run the createPurchase method which is in apiPurchases
			$response = activatePurchase($bookId, $username, $token, $PayerID);

			echo json_encode($response);
		}
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a GET request."));
	}

?>