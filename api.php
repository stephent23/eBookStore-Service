<?php

	//require the database connection and the common methods class
	require 'databaseConnection.php';
	require 'common.php';
	require 'config.php';

	function userCreate($username, $password, $email) { 
		$hashedPassword = hashPassword($password);

		//check that the email address is valid
		if (checkEmail($email) == false) {
			return array("success" => false, "message" => "Invalid Email Address.");
		}

		//check whether the email address given already exists in the database
		$emailExists = checkEmailExists($email);
		if($emailExists == true) {
			return array("success" => false, "message" => "Email address given already exists.");
		}
		else if($emailExists == "Failed") {
			return array("success" => false, "message" => "Something went wrong, please try again.");
		}

		//check whether the username already exists in the database
		$usernameExists = checkUsernameExists($username);
		if($usernameExists == true) {
			return array("success" => false, "message" => "Username already exists.");
		}
		else if($usernameExists == "Failed") {
			return array("success" => false, "message" => "Something went wrong, please try again.");
		}

		$parameters = array(":username" => $username, ":password" => $hashedPassword, ":email" => $email, ":type" => constant('ACC_TYPE_USER'));
		$sql = "INSERT INTO users (username, password, email, type) VALUES (:username, :password, :email, :type)";

		try {
			$connection = connectToDatabase();
			$query = $connection->prepare($sql);
			$query->execute($parameters);

			return array("success" => true, "message" => "User account created.");
		} 
		catch (PDOException $exception) { 
			return array("success" => false, "message" => "Something went wrong, please try again.");
		}
	}

	function login($username, $password) {
		$hashedPassword = hashPassword($password);

		$parameters = array(":username" => $username, ":password" => $hashedPassword);
		$sql = "SELECT * FROM users WHERE username=:username AND password=:password";

		try { 
			$connection = connectToDatabase();
			$query = $connection->prepare($sql);
			$query->execute($parameters);

			$result = $query->fetchAll();
		}
		catch (PDOException $exception) {
			return array("success" => false, "message" => "Something went wrong, please try again.");
		}

		if(count($result) == 1) {
			$userToLogin = $result[0];
			$_SESSION['username'] = $userToLogin['username'];
			$_SESSION['authority'] = $userToLogin['type'];

			return array("success" => true, "message" => "Login Successful");
		}
		else {
			return array("success" => false, "message" => "Incorrect Credentials");
		}
	}

?>