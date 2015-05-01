<?php

	require_once 'apiReviews.php';

	//only accepts post requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "POST") {

		//CHECK ALL FIELDS ARE FILLED OUT
		//check that the inputs that are string/int are not empty 
		//list of the required input fields in the form
		$requiredInput = array('book_id', 'user', 'review', 'rating');
		foreach($requiredInput as $input) {
	  		if (empty($_POST[$input])) {
	    		echo json_encode(array("success" => False, "message" => "The request sent contained empty fields."));
	  		}
		}

		//assign each form input to a variable 
		$bookId = $_POST['book_id'];
		$username = $_POST['user'];
		$review = $_POST['review'];
		$rating = $_POST['rating'];


		//run the createReview method which is in apiReviews
		$response = updateReview($bookId, $username, $review, $rating);

		echo json_encode($response);
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a POST request."));
	}

?>