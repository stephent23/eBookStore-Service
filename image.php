<?php

	require_once 'apiBooks.php';

	//only accepts GET requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "GET") {
		//assign form input to a variable 
		$bookId = $_GET['book_id'];

		//run the getImage method which is in apiBooks
		$response = getImage($bookId);
		if ($response != False) {
			$fp = fopen($response, 'rb');

			//send the headers to the webpage so that it downloads the jpeg
			header("Content-Type: image/jpg");
			header("Content-Length: " . filesize($response));

			//download the picture
			fpassthru($fp);
			//stop the script
			exit;
		}
		else {
			//return HTTP 404 status code
			http_response_code(404);
		}

	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a POST request."));
	}

?>