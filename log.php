<?php

	require_once 'apiAuditLog.php';

	//only accepts GET requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "GET") {
		//initialise variables
		$start = "";
		$length = "";

		//assign form input to a variable 
		if (isset($_GET['start'])) {
			$start = $_GET['start'];
		}
		if(isset($_GET['length']))	{
			$length = $_GET['length'];
		}

		//run the getPurchases method which is in apiPurchases
		$response = getLogs($start, $length);
		echo json_encode($response);
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a GET request."));
	}

?>