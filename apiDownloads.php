<?php

	require_once 'config.php';
	require_once 'databaseConnection.php';
	require_once 'common.php';

	function downloadBook($bookId, $username) {
		//CHECK USER AUTHENTICATION
		//check that the user is logged in
		if (!isLoggedIn()) {
			return False;
		}

		//check that the user that is logged in, is of account type 'user'
		if (checkSessionUser() != True) {
			return False;
		}

		//check that the username given matches the username in the session
		if($username != getSessionUsername()) {
			return False;
		}

		//CHESKS THAT THE BOOK IS VALID/PURCHASED
		//checks that a book with the given ID exists
		$checkBookSQL = "SELECT * FROM books WHERE(book_id = :book_id)";
		$checkBookParam = array(":book_id" => $bookId);

		//checks whether a purchase has been made
		$checkPurchaseSQL =  "SELECT * FROM downloads WHERE((book_id = :book_id) AND (username = :username))";
		$checkPurchaseParam = array(":book_id" => $bookId, ":username" => $username);

		//update count on download
		$updateDownloadSQL = "UPDATE downloads SET download_count = download_count + 1 WHERE ((book_id = :book_id) AND (username = :username))";

		try { 
			$connection = connectToDatabase();
				
			//select the book from the database
			$queryCheck = $connection->prepare($checkBookSQL);
			$queryCheck->execute($checkBookParam);
			$bookInformation = $queryCheck->fetch(PDO::FETCH_ASSOC);

			//check that the book exists
			if ($bookInformation != False) {
				//check user has purchased the book
				$queryCheck = $connection->prepare($checkPurchaseSQL);
				$queryCheck->execute($checkPurchaseParam);
				$downloadCount = $queryCheck->fetch(PDO::FETCH_ASSOC);
				//check that the book has been purchased and that it hasn't been downloaded more than 100 times.
				//has to be less than equal to one as if there is nothing 1 is returned, if there is a record then count() = 4
				//this is because of the number of columns to each record.
				if (count($downloadCount) <= 1 || ($downloadCount['download_count'] >= 100)) {
					return False;
				}
				else {
					$content = $bookInformation['content'];
					$queryUpdate = $connection->prepare($updateDownloadSQL);
					$queryUpdate->execute($checkPurchaseParam);

					return $content;
				}
			}
		}
		catch (PDOException $e) {
			return False;
		}
	}

?>