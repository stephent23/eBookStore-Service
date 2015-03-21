<?php
	
	require_once 'apiUserAccounts.php';

	//only accepts post requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "POST") {
		$username = $_POST['username'];
		$password = $_POST['password'];

		//This can be found in the apiUserAccounts.php file
		$response = login($username, $password);

		echo json_encode($response);
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a POST request."));
	}

?>