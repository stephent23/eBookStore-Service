<?php

	require_once 'config.php';
	require_once 'databaseConnection.php';
	require_once 'common.php';
	require_once 'apiAuditLog.php';
	require_once PAYPAL_PHP_SDK . '/vendor/autoload.php';

	use PayPal\Api\Amount;
	use PayPal\Api\Payer;
	use PayPal\Api\Item;
	use PayPal\Api\ItemList;
	use PayPal\Api\Payment;
	use PayPal\Api\RedirectUrls;
	use PayPal\Api\Transaction;
	use PayPal\Api\PaymentExecution;
	use PayPal\Api\ExecutePayment;

	/**
	 * This method checks that the user is legitimate and that the book that they are trying to purchase exists. 
	 * It then checks that the user has not already purchased the book. If they have purchased but not accepted they are sent to the 
	 * payment page (PayPal) otherwise appropriate message is fedback to the user. If it is a legitimate request then the 
	 * PayPal method is invoked.
	 * 
	 * @param  Integer $bookId   The id of the book that is to be purchased.
	 * @param  String $username The username of the user that wants to purchase the book. 
	 * @return Array/Website Appropriate message or redirect to PayPal website.           
	 */
	function createPurchase($bookId, $username) {
		//CHECK USER AUTHENTICATION
		//check that the user is logged in
		if (!isLoggedIn()) {
			return array("success" => False, "message" => "Please log in in order to access this page.");
		}

		//check that the user that is logged in, is of account type 'user'
		if (checkSessionUser() != True) {
			return array("success" => False, "message" => "Only users are able to purchase books.");
		}

		//check that the username given matches the username in the session
		if($username != getSessionUsername()) {
			createLogEntry("Create Purchase", getSessionUsername(), "The user that is logged in does not match the username given.");
			return array("success" => False, "message" => "The username given is not the same as the user that is logged in");
		}

		//CHESKS THAT THE BOOK IS VALID/NOT PURCHASED
		//checks that a book with the given ID exists
		$checkBookSQL = "SELECT * FROM books WHERE(book_id = :book_id)";
		$checkBookParam = array(":book_id" => $bookId);

		//checks whether a purchase has been made
		$checkPurchaseSQL =  "SELECT * FROM purchases WHERE((book = :book_id) AND (username = :username))";
		$checkPurchaseParam = array(":book_id" => $bookId, ":username" => $username);

		//the variable that will contain the associative array of the book
		$bookInformation = "";

		try { 
			$connection = connectToDatabase();
			
			//select the book from the database
			$queryCheck = $connection->prepare($checkBookSQL);
			$queryCheck->execute($checkBookParam);
			$bookInformation = $queryCheck->fetch(PDO::FETCH_ASSOC);

			//check that the book exists
			if ($bookInformation == False) {
				createLogEntry("Create Purchase", getSessionUsername(), "No book with the given ID exists.");
				return array("success" => False, "message" => "No book with the ID given exists.");
			}

			//checks whether there are already purchases in the database for that book/user
			$queryPurchases = $connection->prepare($checkPurchaseSQL);
			$queryPurchases->execute($checkPurchaseParam);
			$purchaseInformation = $queryPurchases->fetch(PDO::FETCH_ASSOC);

			//check if purchase exists
			if ($purchaseInformation != False) {
				//check if the purchase is complete, if it is inform the user they have puchased the book.
				if ($purchaseInformation['executed'] == 1) {
					createLogEntry("Create Purchase", getSessionUsername(), "The book has already been purchased.");
					return array("success" => False, "message" => "This book has already been purchased.");
				}
			}
		}
		catch (PDOException $exception) {
			//catches the exception if unable to connect to the database
			//return $exception;
			createLogEntry("Create Purchase", getSessionUsername(), "PDO Exception. Transaction aborted.");
			return array("success" => False, "message" => "Something went wrong, please try again.");
		}

		$approvalUrl = payPalCreate($bookInformation, $username);
		//header("Location: $approvalUrl->getApprovalLink()");

	}

	/**
	 * This method creates the payment with PayPal and directs the user to the website. Also, adds the relevant
	 * information to the database.
	 * @param  Array $bookInformation The information about the book that is to be purchased.
	 * @param  String $username       The username of the user that is purchasing the book.
	 * @return Array/Redirect         Appropriate message/Redirect to the PayPal website. 
	 */
	function payPalCreate($bookInformation, $username) {
		$payer = new Payer();
		$payer->setPaymentMethod("paypal");

		// ### Itemized information
		// (Optional) Lets you specify item wise
		// information
		$book = new Item();
		$book->setName($bookInformation['title'])
		    ->setCurrency('USD')
		    ->setQuantity(1)
		    ->setPrice($bookInformation['price']);

		$purchaseItems = new ItemList();
		$purchaseItems->setItems(array($book));

		// ### Cost
		// Lets you specify a payment cost.
		// You can also specify additional details
		// such as shipping, tax.
		$cost = new Amount();
		$cost->setCurrency("USD")
		    ->setTotal($bookInformation['price']);

		// ### Transaction
		// A transaction defines the contract of a
		// payment - what is the payment for and who
		// is fulfilling it. 
		$transaction = new Transaction();
		$transaction->setAmount($cost)
		    ->setItemList($purchaseItems)
		    ->setDescription("This purchase order is for the following book: " + $bookInformation['title'] + ".")
		    ->setInvoiceNumber(uniqid());

		$redirectUrls = new RedirectUrls();
		$baseUrl = constant("SERVICE_URL");
		$bookId = $bookInformation['book_id'];
		$redirectUrls->setReturnUrl("$baseUrl/purchase_activate.php?success=true")
			->setCancelUrl("$baseUrl/purchase_cancel.php?success=false&book_id=$bookId&username=$username");

		// ### Payment
		// A Payment Resource; create one using
		// the above types and intent set to 'sale'
		$payment = new Payment();
		$payment->setIntent("sale")
		    ->setPayer($payer)
		    ->setRedirectUrls($redirectUrls)
		    ->setTransactions(array($transaction));

		// ### Create Payment
		// Create a payment by calling the 'create' method
		// passing it a valid apiContext.
		// (See bootstrap.php for more on `ApiContext`)
		// The return object contains the state and the
		// url to which the buyer must be redirected to
		// for payment approval
		try {
		    $payment->create();
		    createLogEntry("Create Purchase", getSessionUsername(), "Payment created.");
		} catch (PPConnectionException $ex) {
			echo $ex->getData();
			createLogEntry("Create Purchase", getSessionUsername(), "PPConnection Exception. Transaction unable to be created with PayPal.");
			exit(0);
		}

		//GET THE LINK THAT THE USER WILL BE REDIRECTED TO
		$approvalUrl = $payment->getApprovalLink();
		$id = $payment->getId();

		$parameters = array(":username" => $username, 
			":book_id" => $bookInformation['book_id'],
			":payment_id" => $id,
			":url" => $approvalUrl);

		$sql = "INSERT INTO purchases (username, book, payment_id, paypal_payment_url) 
			VALUES (:username, :book_id, :payment_id, :url)";

		try {
			$connection = connectToDatabase();
			//Insert the review in into the db
			$queryInsert = $connection->prepare($sql);
			$queryInsert->execute($parameters);

		}
		catch (PDOException $exception) {
			createLogEntry("Create Purchase", getSessionUsername(), "PDO Exception. Transaction created with PayPal but not inserted into database.");
			return array("success" => False, "message" => "Something went wrong, please try again.");
		}

		header("Location: $approvalUrl");
	}

	/**
	 * This method activates the purchase by using the standard methods provided by the PayPal api.
	 * It then updates the database indicating that the book has been purchased and adds the book to the downloads table, 
	 * this is so that the downloads are able to be tracked at a later date.
	 * Records all steps in the audit log, by calling the appropriate functions.
	 * 
	 * @param  Integer $bookId   the id of the book that has been purchased.
	 * @param  String $username the username of the person purchasing the book.
	 * @param  String $token    token sent back once user has accepted the payment.
	 * @param  String $PayerID  payPals ID of the person making the purchase.
	 * @return Array           Appropriate message to user.
	 */
	function activatePurchase($bookId, $username, $token, $PayerID) {
		//CHECK USER AUTHENTICATION
		//check that the user is logged in
		if (!isLoggedIn()) {
			return array("success" => False, "message" => "Please log in in order to access this page.");
		}

		//check that the user that is logged in, is of account type 'user'
		if (checkSessionUser() != True) {
			return array("success" => False, "message" => "Only users are able to purchase books.");
		}

		//check that the username given matches the username in the session
		if($username != getSessionUsername()) {
			createLogEntry("Activate Purchase", getSessionUsername(), "The user that is logged in does not match the username given.");
			return array("success" => False, "message" => "The username given is not the same as the user that is logged in");
		}

		//get the payment ID from the database
		$paymentIDRetrieveSQL = "SELECT * FROM purchases WHERE ((username = :user) AND (book = :bookId))";
		$paymentIDRetrieveParams = array(":user" => $username, ":bookId" => $bookId);

		$paymentID;
		try { 
			$connection = connectToDatabase();
			
			//select the payment ID from the database
			$queryPaymentId = $connection->prepare($paymentIDRetrieveSQL);
			$queryPaymentId->execute($paymentIDRetrieveParams);
			$paymentIDArray = $queryPaymentId->fetch(PDO::FETCH_ASSOC);

			$paymentID = $paymentIDArray['payment_id'];

			//check that the book exists
			if ($paymentID == False) {
				createLogEntry("Activate Purchase", getSessionUsername(), "No payment for the given book and user exists. Token: $token");
				return array("success" => False, "message" => "No book with the ID given exists.");
			}
		}
		catch (PDOException $e) {
			createLogEntry("Activate Purchase", getSessionUsername(), "PDO Exception. Unable to retrieve payment ID from the database.");
			return array("success" => False, "message" => "Something went wrong, please try again.");
		}

		try {
			//Get the payment object
			$payment = Payment::get($paymentID);
		}
		catch (PayPal\Exception\PayPalConnectionException $e) {
			return array("success" => False, "message" => "Unable to get payment.");
			createLogEntry("Activate Purchase", getSessionUsername(), "PPConnection Exception. Unable to retrieve payment from PayPal.");
			exit(0);
		}
		//Create a new payment execution and assign the payer ID
		$execution = new PaymentExecution(); 
		$execution->setPayerId($PayerID);

		$executed = "";
		try {
			$executed = $payment->execute($execution);
		}
		catch (PayPal\Exception\PayPalConnectionException $e) {
			return array("success" => False, "message" => "Unable to execute payment.");
			createLogEntry("Activate Purchase", getSessionUsername(), "PPConnection Exception. Transaction unable to be executed with PayPal.");
			exit(0);
		}

		//update purchases
		$purchasedSQL = "UPDATE purchases SET `executed`=1 WHERE (payment_id = :paymentID)";
		$purchasedParams = array(":paymentID" => $paymentID);

		//add to downloads
		$downloadsInsertSQL = "INSERT INTO downloads (username, book_id) 
			VALUES (:username, :book_id)";
		$downloadsInsertParam = array(":username" => $username, ":book_id" => $bookId);

		try { 
			$connection = connectToDatabase();
			
			//update the puchase to executed in the database
			$queryPaymentId = $connection->prepare($purchasedSQL);
			$queryPaymentId->execute($purchasedParams);
			createLogEntry("Activate Purchase", getSessionUsername(), "Payment activated successfully. Book ID: $bookId purchased. Token: $token");

			//add book to downloads table
			$downloadsInsert = $connection->prepare($downloadsInsertSQL);
			$downloadsInsert->execute($downloadsInsertParam);
			createLogEntry("Activate Purchase", getSessionUsername(), "Added to downloads. Book ID: $bookId.");

		}
		catch (PDOException $e) {
			createLogEntry("Activate Purchase", getSessionUsername(), "PDO Exception. Unable to update executed to true in the database. PayPal payment executed. Book ID: $bookId . Token: $token");
			return array("success" => False, "message" => "Something went wrong, please try again.");
		}

		return array("success" => True, "message" => "Payment successful, book purchased. Thank you.");

	}

	/**
	 * Returns the bookId and the username associated with the payment
	 * @param  String $paymentId The payment Id of the transaction
	 * @return Array            Array containing the username and the bookId associcated with the transaction
	 */
	function getPurchaseInfo($paymentId) {
		//get the payment ID from the database
		$sql = "SELECT username, book FROM purchases WHERE (payment_id = :paymentId)";
		$params = array(":paymentId" => $paymentId);

		try { 
			$connection = connectToDatabase();
			
			//select the payment ID from the databases
			$query = $connection->prepare($sql);
			$query->execute($params);
			$result = $query->fetch(PDO::FETCH_ASSOC);

			return $result;
		}
		catch (PDOException $e) {
			createLogEntry("getPurchaseInfo", getSessionUsername(), "PDO Exception. Unable to retrieve payment info from the database.");
			return array("success" => False, "message" => "Something went wrong, please try again.");
		}
	}

	/**
	 * This cancels the purchase and updates the purchase table.
	 * @param  String $token The token that is returned from PayPal.
	 * @return None
	 */
	function cancelPurchase($token, $username, $book) { 
		if(checkSessionUser() == True) { 
			$sql = "UPDATE purchases SET cancelled = 1 WHERE ((username = :user) AND (book = :book_id) AND (executed = :executed))";
			$params = array(":user" => $username, ":book_id" => $book, ":executed" => 0);
			try { 
				$connection = connectToDatabase();
			
				//from the databases
				$query = $connection->prepare($sql);
				$query->execute($params);
			}
			catch (PDOException $e) {
				echo $e;
				createLogEntry("cancelPurchase", $username, "PDO Exception. Unable to update payment infomation in the database. Payment cancelled with PayPal. Token: $token.");
				exit;
			}
			createLogEntry("cancelPurchase", $username, "Payment cancelled successfully. Book: $book . Token: $token.");
		}
	}

	/**
	 * Returns the purchases that were requested.
	 * Admins are allowed to request purchases for anyone, users are only allowed to request the purchases for themselves.
	 * All params are optional.
	 * @param  String $user   The user that the purchases are being requested for.
	 * @param  Integer $bookId The ID of the book that the purchase is being requested for.
	 * @param  Integer $start  The start point of the purchases.
	 * @param  Integer $length The end point of the purchases.
	 * @return [type]         [description]
	 */
	function getPurchases($user, $bookId, $start, $length) {
		//CHECK AUTHENTICATION
		//check that the user is logged in
		if (!isLoggedIn()) {
			return array("success" => False, "message" => "Please log in in order to access this page.");
		}

		//check that the user that is logged in is an admin or user.
		//If they are a user, ensure they can only get purchases about themself
		if (checkSessionUser() == True) {
			//If no username is given, assume they want the purchases for themself
			if ($user == "") {
				$user = getSessionUsername();
			}
			//otherwise notify them they are unable to request purchases for other users
			else if(getSessionUsername() != $user) {
				return array("success" => False, "message" => "Only administrators are allowed to request to view the purchases of other users.");
			}
		}
		else if (checkSessionAdmin() == False) {
			return array("success" => False, "message" => "Account type not recognised, please try logging in an out again.");
		}

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
		$parameters = array(":username" => $user, ":bookId" => $bookId, ":executed" => 1);
		$sql = "SELECT * FROM purchases WHERE ((username LIKE concat('%', :username, '%')) AND (book LIKE concat('%', :bookId, '%')) AND (executed = :executed))";
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

		//The list of purchases that will be returned 
		$purchases = array();
		//A counter to know where in the list of purchases you are
		$counter = 0;
		if($length != "") {
			$end = $start + $length;
		}
		//Loop through each of the books (results) that have been retrieved from the db
		foreach ($results as $result) {
			//change the key names/orders
			$purchase = array();
			$purchase['book_id'] = $result['book'];
			$purchase['user'] = $result['username'];
			 
			//if length is not set then providing the counter is more than the offset add the book to the list of books
			if($length == "") {
				if ($counter >= $start) {
					array_push($purchases, $purchase);
				}
				$counter++;
			}
			//if the length is set
			else if (($counter >= $start) && ($counter < $end)) {
				array_push($purchases, $purchase);
				$counter++;
			}
			else {
				$counter++;
			}
		}

		return $purchases;
	}

?>