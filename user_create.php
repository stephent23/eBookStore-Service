<?php
	
	include 'common.php';

	$username = $_POST['username'];
	$password = hashPassword($_POST['password']);
	$email = $_POST['email'];
	$type = "user"

	if (checkEmail($email) == false) {
		return json_encode(array("success" => False, "message" => "Invalid Email Address."))
	}

	$parameters = array(":username" => $username, ":password" => $password, ":email" => $email, ":type" => $type)

	$sql = "INSERT INTO users (username, password, email, type) VALUES (:username, :password, :email, :type)";


	$class = new databaseConnection();
	$connection = $class->connectToDatabase();

	$query = $connection->prepare($sql);
	$query->execute($parameters);

	return json_encode(array("success" => True, "message" => "User account created.")

?>