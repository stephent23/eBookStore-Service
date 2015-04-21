<?php

	require_once 'apiReviews.php';

	//only accepts GET requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "GET") {
		//assign form input to a variable 
		$reviewId = $_GET['review_id'];

		//run the getReview method which is in apiReviews
		$response = getReview($reviewId);

		echo json_encode($response);

	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a GET request."));
	}

?>