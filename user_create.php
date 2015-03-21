<?php
	
	require_once 'apiUserAccounts.php';

	//check that the request method is a post request. No other request type is permitted.
	if($_SERVER['REQUEST_METHOD'] == "POST") {
		$username = $_POST['username'];
		$password = $_POST['password'];
		$email = $_POST['email'];

		//This can be found in the apiUserAccounts.php file
		$response = userCreate($username, $password, $email);

		echo json_encode($response);
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a POST request."));
	}



?>