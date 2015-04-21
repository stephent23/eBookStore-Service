<?php

	//require the database connection and the common methods class
	require_once 'databaseConnection.php';
	require_once 'common.php';

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
		$requiredInput = array($title, $authors, $description, $price);
		foreach($requiredInput as $input) {
	  		if (empty($input)) {
	    		return array("success" => False, "message" => "The request sent contained empty fields.");
	  		}
		}

		//check whether the file upload fields are empty
		if($_FILES["image"]["error"] == 4) {
			return array("success" => False, "message" => "The image field cannot be left empty.");
		}
		if($_FILES["content"]["error"] == 4) {
			return array("success" => False, "message" => "The content field cannot be left empty.");
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
		$target_dir_image = "../../../assessment2_data/sjt43/public_html/uploads/images";
		$target_file_image = $target_dir_image . basename($image['name']);
		$target_dir_content = "../../../assessment2_data/sjt43/public_html/uploads/content/";
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


	/**
	 * Queries the database with the book id and returns the image file path otherwise returns false
	 * @param  Int The id of the book in the database
	 * @return String/Boolean The file path of the image or False.
	 */
	function getImage($bookId) {
		//SANITISE INPUT
		if (!is_numeric($bookId)) {
			return False;
		}

		//EXECUTE
		//Build the parameters and assign the bookId
		$parameters = array(":id" => $bookId);
		$sql = "SELECT * FROM books WHERE book_id=:id";

		try { 
			$connection = connectToDatabase();
			$query = $connection->prepare($sql);
			$query->execute($parameters);

			//retrieve the array of the record
			$result = $query->fetch();
			
			//check that the result is not null, if not return the image path
			if (!is_null($result)) {
				$image = $result['image']; 
				return $image;
			}
			else {
				return False;
			}
			
		}
		catch (PDOException $exception) {
			//catches the exception if unable to connect to the database
			return False;
		}
	}

	/**
	 * Queries the database table books with two optional parameters, title and authors. 
	 * Selects anything that contains what is in those parameters. 
	 * Then loops through what was retrieved and returns the books that are between the offset and the offset + length.
	 * @param  String $title   A string that appears within the title of a book.
	 * @param  String $authors A string that appears within the title of the authors.
	 * @param  Integer $start   A number where the loop will start at when looping through the books.
	 * @param  Integer $length  A number where the loop will end at when looping through the books. This is added to the start variable.
	 * @return Array  An array of books or the errror message.
	 */
	function getBooks($title, $authors, $start, $length) {
		//SANITISE INPUT
		//check that start is either equal to none or a number  
		
		if ($start != "") {
			if (!is_numeric($start)) { 
				return array("success" => False, "message" => "Start and Length inputs have to be numeric.");
			}
		}
		else { 
			//If an offset is not specified then set it to zero
			$start = 0;
		}
		//check that the length is either equal to none or a number
		if($length != "") {
			if (!is_numeric($length)) {
				return False; 
			}
		}

		//EXECUTE
		//Build the parameters array with the correct parameters
		$parameters = array(":title" => $title, ":authors" => $authors);
		$sql = "SELECT * FROM books WHERE ((title LIKE concat('%', :title, '%')) AND (authors LIKE concat('%', :authors, '%')))";
		$results;
		try {
			$connection = connectToDatabase();
			$query = $connection->prepare($sql);
			$query->execute($parameters);

			//retrieve the array of the record
			$results = $query->fetchAll(PDO::FETCH_ASSOC);
			
		}
		catch (PDOException $exception) {
			//catches the exception if unable to connect to the database
			return array("success" => False, "message" => "Something went wrong, please try again.");
		}

		//The list of books that will be returned 
		$books = array();
		//A counter to know where in the list of books you are
		$counter = 0;
		//Loop through each of the books (results) that have been retrieved from the db
		foreach ($results as $book) {
			//Remove the image and the content fields from the arrays
    		unset($book['image']);
			unset($book['content']);
			 
			//if length is not set then providing the counter is more than the offset add the book to the list of books
			if($length == "") {
				if ($counter >= $start) {
					array_push($books, $book);
				}
				$counter++;
			}
			//if the length is set
			else if (($counter >= $start) &&  ($counter < $length)) {
				array_push($books, $book);
				$counter++;
			}
		}

		return $books;
	}

	/**
	 * Given a valid book ID this method will query the database for the information associated with that book as well as any
	 * associated review_ids. If no book is found it will return an appropriate response.
	 * @param  Integer $bookId The ID of the book that is to be queried.
	 * @return Array           An array containing the success as a boolean and then the information about the book/reviews or a message
	 *                            explaining any issues.
	 */
	function getBook($bookId) { 
		if (!is_numeric($bookId)) {
			return array("success" => False, "message" => "Incorrect Data Type: Book ID has to be an integer.");
		}

		$parameters = array(":bookId" => $bookId);
		$sqlBook = "SELECT book_id, title, authors, description, price FROM books
		 				WHERE (book_id = :bookId)";

		 $sqlReviews = "SELECT review_id FROM reviews WHERE (book_id = :bookId)";


		$results;
		try {
			$connection = connectToDatabase();
			//retrieve the array of the book
			$queryBook = $connection->prepare($sqlBook);
			$queryBook->execute($parameters);
			$book = $queryBook->fetch(PDO::FETCH_ASSOC);

			//retreive the array of the review IDs
			$queryReviews = $connection->prepare($sqlReviews);
			$queryReviews->execute($parameters);
			$reviews = $queryReviews->fetchAll(PDO::FETCH_ASSOC);

			if($book != False) {
				return array("success" => True, "book" => $book, "reviews" => $reviews);
			}
			else {
				return array("success" => False, "message" => "A book with the given ID does not exist.");
			}
		}
		catch (PDOException $exception) {
			//catches the exception if unable to connect to the database
			return $exception;
			return array("success" => False, "message" => "Something went wrong, please try again.");
		}
	}

?>