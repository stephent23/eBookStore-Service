<?php
	
	require_once 'api.php';

	if($_SERVER['REQUEST_METHOD'] == "POST") {
		$username = $_POST['username'];
		$password = $_POST['password'];
		$email = $_POST['email'];

		$response = userCreate($username, $password, $email);

		echo json_encode($response);
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a POST request."));
	}



?>