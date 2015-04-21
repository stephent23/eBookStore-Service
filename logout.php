<?php

	//only accepts GET requests. No other HTTP request method.
	//This is not requiring to call any method in the apiUserAccounts.php file as no access to the database is needed
	if($_SERVER['REQUEST_METHOD'] == "GET") {
		session_start();

		if (isset($_SESSION['username'])) {
			//remove all the session variables
			session_unset();
			//destroy the session
			session_destroy();

			echo json_encode(array("success" => True, "message" => "Logout successful."));
		}
		else {
			echo json_encode(array("success" => False, "message" => "Unable to logout. No user was logged in."));
		}
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a POST request."));
	}

?>