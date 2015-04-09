<?php

	//require the database connection and the common methods class
	require 'databaseConnection.php';
	require 'common.php';

	function createReview($bookId, $username, $review, $rating) {
		//CHECK USER AUTHENTICATION
		//check that the user is logged in
		if (!isLoggedIn()) {
			return array("success" => False, "message" => "Please log in in order to access this page.");
		}

		//check that the user that is logged in, is an admin
		if (checkSessionUser() != True) {
			return array("success" => False, "message" => "Only users are able to create books.");
		}

		//check that the username given matches the username in the session
		if($username != getSessionUsername()) {
			return array("success" => False, "message" => "The username given is not the same as the user that is logged in");
		}

		//CHECK ALL FIELDS ARE FILLED OUT
		//check that the inputs that are string/int are not empty 
		//list of the required input fields in the form
		$requiredInput = array($bookId, $review, $rating);
		foreach($requiredInput as $input) {
	  		if ($input == "" || $input == None) {
	    		return array("success" => False, "message" => "The request sent contained empty fields.");
	  		}
		}

		//SANITISE INPUT
		//check that rating is a number and that it is between 1 and 5
		if (is_numeric($rating)) { 
			if (($rating < 0) || ($rating > 5)) {
				return array("success" => False, "message" => "Rating has to be between 0 and 5.");
			}
		}
		else {
			return array("success" => False, "message" => "Rating has to be an integer between 0 and 5.");
		}

		$parameters = array(":username" => $username, 
			":book_id" => $bookId,
			":review" => $review,
			"rating" => $rating);

		$checkBookParam = array(":book_id" => $bookId);
		$checkBookSQL = "SELECT COUNT(*) FROM books WHERE(book_id = :book_id)";

		$sql = "INSERT INTO reviews (book_id, username, review, rating) 
			VALUES (:book_id, :username, :review, :rating)";

		try { 
			$connection = connectToDatabase();
			//check book exists 
			$queryCheck = $connection->prepare($checkBookSQL);
			$queryCheck->execute($checkBookParam);
			$count = $queryCheck->fetch(PDO::FETCH_NUM);
			if ($count[0] != 1) {
				return array("success" => False, "message" => "No book with the ID given exists.");
			}

			//Insert the review in into the db
			$queryInsert = $connection->prepare($sql);
			$queryInsert->execute($parameters);

			$resultId = $connection->lastInsertId();
			return array("success" => True, "message" => "Review has been created.", "review_id" => $resultId);
		}
		catch (PDOException $exception) {
			//catches the exception if unable to connect to the database
			return $exception;
			return array("success" => False, "message" => "Something went wrong, please try again.");
		}


	}

?>