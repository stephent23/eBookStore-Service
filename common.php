<?php

	require_once 'databaseConnection.php';

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