<?php

	//only accepts GET requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "GET") {
		//check if get resquest is from paypal redirect
		if(isset($_GET['success'])) {
			if($_GET['success'] == "true") {
				echo json_encode(array("success" => True, "message" => "Payment successful"));
			}
			elseif($_GET['success'] == "false") {
				echo json_encode(array("success" => False, "message" => "Payment Cancelled"));
			}
		}
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a GET request."));
	}

?>