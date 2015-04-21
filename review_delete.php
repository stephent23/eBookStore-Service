<?php

	require_once 'apiReviews.php';

	//only accepts post requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "GET") {

		//assign each form input to a variable 
		$reviewId = $_GET['review_id'];

		//run the deleteReview method which is in apiReviews
		$response = deleteReview($reviewId);

		echo json_encode($response);
	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a POST request."));
	}

?>