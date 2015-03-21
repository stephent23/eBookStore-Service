<?php

	//require the database connection and the common methods class
	require 'databaseConnection.php';
	require 'config.php';

	function userCreate($username, $password, $email) { 
		$encryptedPassword = encrypt($password);

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

		//set up the query to insert the user into the database. Uses a constant for the account type, this is defined in config.php
		$parameters = array(":username" => $username, ":password" => $encryptedPassword, ":email" => $email, ":type" => constant('ACC_TYPE_USER'));
		$sql = "INSERT INTO users (username, password, email, type) VALUES (:username, :password, :email, :type)";

		try {
			//executes the query
			$connection = connectToDatabase();
			$query = $connection->prepare($sql);
			$query->execute($parameters);

			return array("success" => true, "message" => "User account created.");
		} 
		catch (PDOException $exception) { 
			//catches the exception if unable to connect to the database
			return array("success" => false, "message" => "Something went wrong, please try again.");
		}
	}

	//This is the function that is called to log the user in
	function login($username, $password) {
		//This is a method that is defined in common.php
		$encryptedPassword = encrypt($password);

		$parameters = array(":username" => $username, ":password" => $encryptedPassword);
		$sql = "SELECT * FROM users WHERE username=:username AND password=:password";

		try { 
			$connection = connectToDatabase();
			$query = $connection->prepare($sql);
			$query->execute($parameters);

			$result = $query->fetchAll();
		}
		catch (PDOException $exception) {
			//catches the exception if unable to connect to the database
			return array("success" => false, "message" => "Something went wrong, please try again.");
		}

		//checks that there is a user with the username and password that has been provided
		if(count($result) == 1) {
			$userToLogin = $result[0];
			//sets the session - one to hold the username and one to hold the account type of the user.
			session_start();
			$_SESSION['username'] = $userToLogin['username'];
			$_SESSION['authority'] = $userToLogin['type'];

			return array("success" => true, "message" => "Login Successful");
		}
		else {
			return array("success" => false, "message" => "Incorrect Credentials");
		}
	}

	//checks that the email provided is a valid email address
	function checkEmail($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	//encrypts the password with a given, generic application salt
	function encrypt($password) {
		$salt = "4gfh21xdb231j54xdf51gbxgf8juser34";
		return crypt($password, $salt);
	}

	//checks whether the username already exists in the database
	function checkUsernameExists($username) { 
		$parameters = array(":username" => $username);
		$sql = "SELECT * FROM users WHERE username=:username";

		try {
			$connection = connectToDatabase();
			$query = $connection->prepare($sql);
			$query->execute($parameters);

			$result = $query->fetchAll();

			if(count($result) > 0) {
				return True;
			}
			else {
				return False;
			}
		}
		catch (PDOException $exception) {
			return "Failed";
		}

	}

	//checks whether the email already exists in the database
	function checkEmailExists($email) { 
		$parameters = array(":email" => $email);
		$sql = "SELECT * FROM users WHERE email=:email";

		try {
			$connection = connectToDatabase();
			$query = $connection->prepare($sql);
			$query->execute($parameters);

			$result = $query->fetchAll();

			if(count($result) > 0) {
				return True;
			}
			else {
				return False;
			}
		}
		catch (PDOException $exception) {
			return "Failed";
		}
	}

?>