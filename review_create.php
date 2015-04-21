<?php

	require_once 'apiReviews.php';

	//only accepts post requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "POST") {

		//assign each form input to a variable 
		$bookId = $_POST['book_id'];
		$username = $_POST['user'];
		$review = $_POST['review'];
		$rating = $_POST['rating'];


		//run the createReview method which is in apiReviews
		$response = createReview($bookId, $username, $review, $rating);

		echo json_encode($response);
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a POST request."));
	}

?>