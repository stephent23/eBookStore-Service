<?php

	//require the database connection and the common methods class
	require 'databaseConnection.php';
	require 'common.php';

	/**
	 * Creates a review in the database, that is associated with the user logged in and a book that is already in the database
	 * @param  Integer $bookId   The id of the book that the review is for
	 * @param  String $username The username of the person that has written the review
	 * @param  String $review   The content of the review (text)
	 * @param  Integer $rating   The rating of the book (Should be between 1-5)
	 * @return Array           The success of failure of the insertion
	 */
	function createReview($bookId, $username, $review, $rating) {
		//CHECK USER AUTHENTICATION
		//check that the user is logged in
		if (!isLoggedIn()) {
			return array("success" => False, "message" => "Please log in in order to access this page.");
		}

		//check that the user that is logged in, is of account type 'user'
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

		$checkBookParam = array(":book_id" => $bookId);
		$checkBookSQL = "SELECT COUNT(*) FROM books WHERE(book_id = :book_id)";

		$checkReviewParam = array(":book_id" => $bookId, ":username" => $username);
		$checkReviewSQL = "SELECT COUNT(*) FROM reviews WHERE(book_id = :book_id AND username = :username)";

		$parameters = array(":username" => $username, 
			":book_id" => $bookId,
			":review" => $review,
			"rating" => $rating);

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

			//check if a review for book has already been added by the user
			$queryCheckReview = $connection->prepare($checkReviewSQL);
			$queryCheckReview->execute($checkReviewParam);
			$countReview = $queryCheckReview->fetch(PDO::FETCH_NUM);
			if ($countReview[0] != 0) {
				return array("success" => False, "message" => "You have already created a review for this book. Please use the update feature if you would like to change it.");
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

	/**
	 * This method allows a review to be updated, providing that the user that is trying to update it is the same user that created it.
	 * @param  Integer $bookId   The id of the book that is to be updated
	 * @param  String $username The username of the user that wants to update the review
	 * @param  String $review   The review that is given itself, replaces the current review in the database
	 * @param  Integer $rating   The rating that the book should be given, between 1 and 5. Replaces the review that is already in the database.
	 * @return array           An array stating the the success/failure and an associated message.
	 */
	function updateReview($bookId, $username, $review, $rating) {
		//CHECK USER AUTHENTICATION
		//check that the user is logged in
		if (!isLoggedIn()) {
			return array("success" => False, "message" => "Please log in in order to access this page.");
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

		$checkBookParam = array(":book_id" => $bookId);
		$checkBookSQL = "SELECT COUNT(*) FROM books WHERE(book_id = :book_id)";

		$checkReviewParam = array(":book_id" => $bookId, ":username" => $username);
		$checkReviewSQL = "SELECT COUNT(*) FROM reviews WHERE(book_id = :book_id AND username = :username)";

		$parameters = array(":username" => $username, 
			":book_id" => $bookId,
			":review" => $review,
			"rating" => $rating);

		$sql = "UPDATE reviews SET review = :review, rating = :rating WHERE (book_id = :book_id AND username = :username)";

		try { 
			$connection = connectToDatabase();
			
			//check book exists 
			$queryCheck = $connection->prepare($checkBookSQL);
			$queryCheck->execute($checkBookParam);
			$count = $queryCheck->fetch(PDO::FETCH_NUM);
			if ($count[0] != 1) {
				return array("success" => False, "message" => "No book with the ID given exists.");
			}

			//check if a review for book has already been added by the user
			$queryCheckReview = $connection->prepare($checkReviewSQL);
			$queryCheckReview->execute($checkReviewParam);
			$countReview = $queryCheckReview->fetch(PDO::FETCH_NUM);
			if ($countReview[0] != 1) {
				return array("success" => False, "message" => "You have not previously created a review for this book. Please use the create review feature to create one.");
			}

			//Update the review in the db
			$queryUpdate = $connection->prepare($sql);
			$queryUpdate->execute($parameters);
			return array("success" => True, "message" => "Review has been successfully updated.");

		}
		catch (PDOException $exception) {
			//catches the exception if unable to connect to the database
			return $exception;
			return array("success" => False, "message" => "Something went wrong, please try again.");
		}


	}

	/**
	 * This method returns the details of a review, given the review id.
	 * @param  Integer $reviewId The ID of the review that is to be retrieved from the database.
	 * @return Array           The review itself or if it doesn't exist an appropriate message. 
	 */
	function getReview($reviewId) { 
		if (!is_numeric($reviewId)) {
			return array("success" => False, "message" => "Incorrect Data Type: Review ID has to be an integer.");
		}

		$parameters = array(":reviewId" => $reviewId);
		$sql = "SELECT book_id, username, review, rating FROM reviews
		 				WHERE (review_id = :reviewId)";

		try {
			$connection = connectToDatabase();
			//retrieve the array of the review
			$queryReview = $connection->prepare($sql);
			$queryReview->execute($parameters);
			$review = $queryReview->fetch(PDO::FETCH_ASSOC);

			if($review != False) {
				return array("success" => True, "book_id" => $review['book_id'], "user" => $review['username'], 
					"review" => $review['review'], "rating" => $review['rating']);
			}
			else {
				return array("success" => False, "message" => "A review with the given ID does not exist.");
			}
		}
		catch (PDOException $exception) {
			//catches the exception if unable to connect to the database
			return $exception;
			return array("success" => False, "message" => "Something went wrong, please try again.");
		}
	}

	/**
	 * Deletes the review with the given ID.
	 * Providing that the review exists (this is handled)
	 * and also providing that they are an admin user or they created the review.
	 * @param  Integer $reviewId The ID of the review to be deleted.
	 * @return Array           An array containing the success (True/False) and the associated message.
	 */
	function deleteReview($reviewId) {
		if (!is_numeric($reviewId)) {
			return array("success" => False, "message" => "Incorrect Data Type: Review ID has to be an integer.");
		}
		
		$parameters = array(":reviewId" => $reviewId);

		$checkUserSql = "SELECT username FROM reviews WHERE (review_id = :reviewId)";
		$sql = "DELETE FROM reviews WHERE (review_id = :reviewId)";
		
		try {
			$connection = connectToDatabase();
			//get the user and see if the review exists
			$checkUser = $connection->prepare($checkUserSql);
			$checkUser->execute($parameters);
			$user = $checkUser->fetch();
			//check if a review with the given ID exists
			if($user == False) {
				return array("success"=>False, "message" => "No review with the given ID exists.");
			}

			//check that the user is admin or they created the review, if not then return message.
			if(!checkSessionAdmin()) {
				if ($user['username'] != getSessionUsername()) {
					return array("success" => False, "message" => "You are not authorised to delete this review. Only the review creator or an admin are only authorised to delete this.");
				}
			}

			$delete = $connection->prepare($sql);
			$delete->execute($parameters);

			return array("success" => True, "message" => "Review deleted.");
		}
		catch (PDOException $exception) {
			//catches the exception if unable to connect to the database
			return $exception;
			return array("success" => False, "message" => "Something went wrong, please try again.");
		}

	}

?>