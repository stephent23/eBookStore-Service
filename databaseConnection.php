<?php
	
	class databaseConnection extends PDO {
		
		function connectToDatabase() {
			$host = 'dragon.kent.ac.uk';
			$dbname = 'sjt43';
			$user = 'sjt43';
			$password = 'octium!';

			try {
				$dbConnection = new PDO ('mysql:host=$host;dbname=$dbname', $user, $password);
				return $dbConnection;
			}
			catch (PDOException $exception) {
				return "Unable to connect to the database";
			}
		}
	}

	$db = new databaseConnection()
	echo $db->connectToDatabase();
?>