<?php

	//require the database connection and the common methods class
	require 'databaseConnection.php';
	require 'common.php';

	/**
	 * This checks that the user is an administrator and if so allows them to create a book. 
	 * Uploads the image (JPEG) and the content (PDF) to a file location and adds the record to the database.
	 * @param  String The title of the book.
	 * @param  String	The author(s) of the book.
	 * @param  String The description of the book.
	 * @param  Int The price/cost of the book.
	 * @param  $_FILES An image of the book. This will be checked that it is a JPEG.
	 * @param  $_FILES The content of the book. This will be checked that it is a PDF.
	 * @return Array An array that contains the type (Successful/Not Succesful (Boolean), 
	 * 					the message and if successful, the id (Primary Key) of the record 
	 * 					that is inserted into the database.
	 */
	function createBook($title, $authors, $description, $price, $image, $content) { 
		
		//CHECK USER AUTHENTICATION
		//check that the user is logged in
		if (!isLoggedIn()) {
			return array("success" => False, "message" => "Please log in in order to access this page.");
		}

		//check that the user that is logged in, is an admin
		if (checkSessionAdmin() != True) {
			return array("success" => False, "message" => "Only admin users are able to create books.");
		}


		//CHECK ALL FIELDS ARE FILLED OUT
		//check that the inputs that are string/int are not empty 
		//list of the required input fields in the form
		$requiredInput = array('title', 'authors', 'description', 'price');
		foreach($requiredInput as $input) {
	  		if (empty($input)) {
	    		echo json_encode(array("success" => False, "message" => "The request sent contained empty fields."));
	    		exit;
	  		}
		}

		//check whether the file upload fields are empty
		if($_FILES["image"]["error"] == 4) {
			echo json_encode(array("success" => False, "message" => "The image field cannot be left empty."));
			exit;
		}
		if($_FILES["content"]["error"] == 4) {
			echo json_encode(array("success" => False, "message" => "The content field cannot be left empty."));
			exit;
		}


		//CHECK THAT INPUTS ARE THE CORRECT FORMAT
		//check the image type is JPEG
		if (exif_imagetype($image['tmp_name']) != IMAGETYPE_JPEG) {
			return array("success" => False, "message" => "Image has an invalid file type. Only image type JPEG are accepted.");
		}

		//check that the content type is PDF
		if ($content['type'] != 'application/pdf') {
			return array("success" => False, "message" => "Content has an invalid file type. Only file type PDF are accepted.");
		}

		//check that the price is a numeric value
		if (!is_numeric($price)) {
			return array("success" => False, "message" => "Price has an invalid data type. Only numeric values are accepted.");
		}


		//EXECUTE
		//setting the target directories for the image and the content
		$target_dir_image = "uploads/images/";
		$target_file_image = $target_dir_image . basename($image['name']);
		$target_dir_content = "uploads/content/";
		$target_file_content = $target_dir_content . basename($content['name']);
		
		//set up the query to insert the details of the book into the database.
		$parameters = array(":title" => $title, 
			":authors" => $authors,
			":description" => $description,
			"price" => $price,
			"image" => $target_file_image,
			"content" => $target_file_content );

		$sql = "INSERT INTO books (title, authors, description, price, image, content) 
			VALUES (:title, :authors, :description, :price, :image, :content)";

		//check that the image and the content have been moved to the correct file path
		if(move_uploaded_file($image['tmp_name'], $target_file_image)) { 
			if(move_uploaded_file($content['tmp_name'], $target_file_content)) {	
				try { 
					$connection = connectToDatabase();
					$query = $connection->prepare($sql);
					$query->execute($parameters);

					$resultId = $connection->lastInsertId();
					return array("success" => True, "message" => "Book has been created.", "book_id" => $resultId);
				}
				catch (PDOException $exception) {
					//catches the exception if unable to connect to the database
					return $exception;
					return array("success" => False, "message" => "Something went wrong, please try again.");
				}
			}
			else {
				return array("success" => False, "message" => "Something went wrong saving the content, please try again.");
			}
		}
		else {
			return array("success" => False, "message" => "Something went wrong saving the image, please try again.");
		}

	}


?>