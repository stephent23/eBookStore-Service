<?php

	require_once 'databaseConnection.php';

	/**
	 * Method runs when there are already logs in the audit table. 
	 * This method adds every subsequent log to the audit table. Using a key/hash chains approach.
	 * It firstly checks that there are already audit logs in the table, if there are not it invokes the method 'startLog'.
	 * @param  String $method   The name of the method that was called. 
	 * @param  String $username The username of the the user that has made the request.
	 * @param  String $outcome  The result of the request.
	 * @return Exception        Would only ever return the exception should there be a problem writing to the database.
	 */
	function createLogEntry($method, $username, $outcome) {
		//set datetime 
		date_default_timezone_set('Europe/London');
    	$datetime = date('Y-m-d H:i:s');

    	//GET THE LAST HASH ADDED TO THE DATABASE
    	$getHash = "SELECT * FROM auditLog ORDER BY datetime DESC LIMIT 1";
    	$getKey = "SELECT * FROM auditKey";
    	$lastHash = "";
    	$currentKey = "";
    	
    	try { 
			$connection = connectToDatabase();

			//select the current key form the database
			$queryGetKey = $connection->prepare($getKey);
			$queryGetKey->execute();
			$result = $queryGetKey->fetch(PDO::FETCH_ASSOC);
			//check if there is no result
			if ($result == False) { 
				startLog($datetime, $method, $username, $outcome);
				return;
			}
			$currentKey = $result['key'];
			
			//select the hash from the database
			$queryGetHash = $connection->prepare($getHash);
			$queryGetHash->execute();
			$result = $queryGetHash->fetch(PDO::FETCH_ASSOC);
			$lastHash = $result['hash'];
		}
		catch (PDOException $e) { 
			return $e;
		}

    	$entry = "$datetime,$method,$username,$outcome";
    	$entryHash = sha1($lastHash.$entry);

    	$log = "INSERT INTO auditLog 
    		(datetime, method_called, logged_in_user, outcome, hash, signature) 
    		VALUES (:datetime, :method_called, :username, :outcome, :hash, AES_ENCRYPT(:hash, :currentKey))";

    	$parameters = array(":datetime" => $datetime, ":method_called" => $method, ":username" => $username, 
    		":outcome" => $outcome, ":hash" => $entryHash, ":currentKey" => $currentKey);

    	//create the new key and add it to database
    	$newKey = sha1($currentKey);
    	$keyQuery = "UPDATE auditKey SET `key` = :key";
    	$keyParam = array(":key" => $newKey);

    	try { 
    		$connection = connectToDatabase();

    		//insert the log
			$query = $connection->prepare($log);
			$query->execute($parameters);

			//insert the new key
			$insertKey = $connection->prepare($keyQuery);
			$insertKey->execute($keyParam);
    	}
    	catch (PDOException $e) {
    		return $e;
    	}
	}

	/**
	 * Method runs when there are no logs in the audit log already. 
	 * This method would only ever run one time, when the audit log is cleared.
	 * @param  DateTime $datetime Current datetime that the transaction was invoked.
	 * @param  String $method   The name of the method that was called. 
	 * @param  String $username The username of the the user that has made the request.
	 * @param  String $outcome  The result of the request.
	 * @return Exception        Would only ever return the exception should there be a problem writing to the database.
	 */
	function startLog($datetime, $method, $username, $outcome) {
		$entry = "$datetime,$method,$username,$outcome";
    	$entryHash = sha1($entry);

    	//insert into audit log
    	$log = "INSERT INTO auditLog 
    		(datetime, method_called, logged_in_user, outcome, hash, signature) 
    		VALUES (:datetime, :method_called, :username, :outcome, :hash, AES_ENCRYPT(:hash, :currentKey))";

    	$parameters = array(":datetime" => $datetime, ":method_called" => $method, ":username" => $username, 
    		":outcome" => $outcome, ":hash" => $entryHash, ":currentKey" => constant("AUDIT_LOG_START_KEY"));

    	//create the next key and add it to database
    	$newKey = sha1(constant("AUDIT_LOG_START_KEY"));
    	$keyQuery = "INSERT INTO auditKey VALUES (:key)";
    	$keyParam = array(":key" => $newKey);
    	

    	try { 
    		$connection = connectToDatabase();

			//insert the log
			$query = $connection->prepare($log);
			$query->execute($parameters);

			//insert the new key
			$insertKey = $connection->prepare($keyQuery);
			$insertKey->execute($keyParam);
    	}
    	catch (PDOException $e) {
    		return $e;
    	}
	}

?>