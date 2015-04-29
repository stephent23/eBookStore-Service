<?php

	require_once __DIR__.'config.php';
	require_once PAYPAL_PHP_SDK . '/vendor/autoload.php';

	use PayPal\Api\Amount;
	use PayPal\Api\Payer;
	use PayPal\Api\Payment;
	use PayPal\Api\RedirectUrls;
	use PayPal\Api\Transaction;
	use PayPal\Api\PaymentExecution;

	function createPurchase($bookId, $username) {
		//CHECK USER AUTHENTICATION
		//check that the user is logged in
		if (!isLoggedIn()) {
			return array("success" => False, "message" => "Please log in in order to access this page.");
		}

		//check that the user that is logged in, is of account type 'user'
		if (checkSessionUser() != True) {
			return array("success" => False, "message" => "Only users are able to create books.");
		}

		//check that the username given matches the username in the session
		if($username != getSessionUsername()) {
			return array("success" => False, "message" => "The username given is not the same as the user that is logged in");
		}

		//CHECK THAT THE GIVEN BOOK ID EXISTS/GET TEH BOOK FROM THE DATABASE
		$checkBookParam = array(":book_id" => $bookId);
		$checkBookSQL = "SELECT * FROM books WHERE(book_id = :book_id)";

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
				return array("success" => False, "message" => "No book with the ID given exists.");
			}
		}
		catch (PDOException $exception) {
			//catches the exception if unable to connect to the database
			return $exception;
			return array("success" => False, "message" => "Something went wrong, please try again.");
		}

		$approvalUrl = payPalCreate($bookInformation, $username);
		header("Location: $approvalUrl");

	}

	function payPalCreate($bookInformation, $username) {
		$payer = new Payer();
		$payer->setPaymentMethod("paypal");

		// ### Itemized information
		// (Optional) Lets you specify item wise
		// information
		$book = new Item();
		$book->setName($bookInformation['title'])
		    ->setCurrency('GBP')
		    ->setQuantity(1)
		    ->setPrice($bookInformation['price']);

		$purchaseItems = new ItemList();
		$purchaseItems->setItems(array($book));

		// ### Cost
		// Lets you specify a payment cost.
		// You can also specify additional details
		// such as shipping, tax.
		$cost = new Amount();
		$cost->setCurrency("GBP")
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
		$redirectUrls->setReturn_url("http://raptor.kent.ac.uk/proj/co639/assessment2/sjt43/purchase_create.php?success=true"); 
		$redirectUrls->setCancel_url("http://raptor.kent.ac.uk/proj/co639/assessment2/sjt43/purchase_create.php?success=false");

		// ### Payment
		// A Payment Resource; create one using
		// the above types and intent set to 'sale'
		$payment = new Payment();
		$payment->setIntent("sale")
		    ->setPayer($payer)
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
		} catch (Exception $ex) {
			echo $ex;
			exit(0);
		}

		//GET THE LINK THAT THE USER WILL BE REDIRECTED TO
		$approvalUrl = $payment->getApprovalLink();
		$id = $payment->getId();

		$parameters = array(":username" => $username, 
			":book_id" => $bookInformation['book_id'],
			"payment_id" => $id);

		$sql = "INSERT INTO purchases (username, book, payment_id) 
			VALUES (:book_id, :username, :payment_id)";

		try {
			$connection = connectToDatabase();
			
			//Insert the review in into the db
			$queryInsert = $connection->prepare($sql);
			$queryInsert->execute($parameters);
		}
		catch (PDOException $exception) {
			return array("success" => False, "message" => "Something went wrong, please try again.");
		}

		return $approvalUrl;
	}

?>