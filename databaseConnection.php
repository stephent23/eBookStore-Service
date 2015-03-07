<?php

	include_once 'config.php';

	function connectToDatabase() {

		try {
			$dbConnection = new PDO("mysql:host=" . constant("DB_HOST") . ";dbname=" . constant("DB_DATABASE"), constant("DB_USERNAME"), constant("DB_PASSWORD"));
			//Ensure that any errors throw exceptions and not errors
			$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			return $dbConnection;
		}
		catch (PDOException $exception) {
			return $exception;
			// return "Unable to connect to the database";
		}
	}

?>