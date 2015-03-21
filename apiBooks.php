<?php

	//require the database connection and the common methods class
	require 'databaseConnection.php';
	require 'common.php';

	function createBook($title, $authors, $description, $price, $image, $content) { 
		//check that the user is logged in
		if (!isLoggedIn()) {
			return array("success" => False, "message" => "Please log in in order to access this page.");
		}

		//check that the user that is logged in, is an admin
		if (checkSessionAdmin() != True) {
			return array("success" => False, "message" => "Only admin users are able to create books.");
		}

		if (exif_imagetype($image['tmp_name']) != IMAGETYPE_JPEG) {
			return array("success" => False, "message" => "Image has an invalid file type. Only image type JPEG are accepted.");
		}

		if ($content['type'] != 'application/pdf') {
			return array("success" => False, "message" => "Content has an invalid file type. Only file type PDF are accepted");
		}

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

		//check that the image and the content are able to be moved to the correct file path
		if(move_uploaded_file($image['tmp_name'], $target_file_image)) { 
			if(move_uploaded_file($content['tmp_name'], $target_file_content)) {	
				try { 
					$connection = connectToDatabase();
					$query = $connection->prepare($sql);
					$query->execute($parameters);

					$result = $query->fetchAll();
					return array("success" => True, "message" => "Book has been created.", "book_id" => $result['id']);
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