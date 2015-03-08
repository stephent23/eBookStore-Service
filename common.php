<?php

	require_once 'databaseConnection.php';

	function checkEmail($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	function hashPassword($password) {
		$salt = "4gfh21xdb231j54xdf51gbxgf8juser34";
		return sha1($salt . $password);
	}

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