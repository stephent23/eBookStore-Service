<?php

	require_once 'apiDownloads.php';

	//only accepts GET requests. No other HTTP request method.
	if($_SERVER['REQUEST_METHOD'] == "GET") {

		//CHECK ALL FIELDS ARE FILLED OUT
		//check that the inputs that are string/int are not empty 
		//list of the required input fields in the form
		$requiredInput = array('book_id', 'user');
		foreach($requiredInput as $input) {
	  		if (empty($_GET[$input])) {
	    		echo json_encode(array("success" => False, "message" => "The request sent contained empty fields."));
	    		exit;
	  		}
		}

		//assign form input to a variable 
		$bookId = $_GET['book_id'];
		$username = $_GET['user'];

		//run the downloadBook method which is in apiDownloads
		$response = downloadBook($bookId, $username);

		if ($response != False) {
			$fp = fopen($response, 'rb');

			//send the headers to the webpage so that it downloads the pdf/content
			header("Content-Type: application/pdf");
			header("Content-Length: " . filesize($response));

			//download the content
			fpassthru($fp);
			//stop the script
			exit;
		}
		else {
			//return HTTP 403 status code
			http_response_code(403);
		}

	}
	else { 
		echo json_encode(array("success" => False, "message" => "The request sent was not a GET request."));
	}

?>