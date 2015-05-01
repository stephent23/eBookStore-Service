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
				//check if the purchase is complete, if it is inform the user they have puchased the book otherwise take them to the URL to accept the purchase.
				if ($purchaseInformation['executed'] == 0) {
					createLogEntry("Create Purchase", getSessionUsername(), "Purchase has already been created but transaction is yet to be completed.");
					$url = $purchaseInformation['paypal_payment_url'];
					header("Location: $url");
					exit(1);
				}
				else {
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
		$redirectUrls->setReturnUrl("$baseUrl/payPalRedirect.php?success=true")
			->setCancelUrl("$baseUrl/payPalRedirect.php?success=false");

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
				createLogEntry("Activate Purchase", getSessionUsername(), "No payment for the given book and user exists.");
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
		catch (PPConnectionException $e) {
			echo $ex->getData();
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
		catch (PPConnectionException $e) {
			echo $ex->getData();
			createLogEntry("Activate Purchase", getSessionUsername(), "PPConnection Exception. Transaction unable to be executed with PayPal.");
			exit(0);
		}

		$purchasedSQL = "UPDATE purchases VALUES executed='True' WHERE (payment_id = :paymentID)";
		$purchasedParams = array(":paymentID" => $paymentID);

		try { 
			$connection = connectToDatabase();
			
			//update the puchase to executed in the database
			$queryPaymentId = $connection->prepare($purchasedSQL);
			$queryPaymentId->execute($purchasedParams);

		}
		catch (PDOException $e) {
			createLogEntry("Activate Purchase", getSessionUsername(), "PDO Exception. Unable to update executed to true in the database. PayPal payment executed.");
			return array("success" => False, "message" => "Something went wrong, please try again.");
		}

		echo $executed;
		createLogEntry("Activate Purchase", getSessionUsername(), "Payment activated successfully. Book purchased.");
		return array("success" => True, "message" => "Payment activated successfully. Book purchased. Thank you.");

	}

?>