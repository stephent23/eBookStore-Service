<?php

	require 'config.php';

	//Checks that the session is set (i.e. a user is logged in)
	function isLoggedIn() {
		session_start();
		if(isset($_SESSION['username'])) {
			return True;
		}
		return False;
	}

	//Checks that the authority session is set to admin
	function checkSessionAdmin() {
		session_start();
		if ($_SESSION['authority'] == constant('ACC_TYPE_ADMIN')) {
			return True;
		}
		return False;
	}

	//Checks that the authority session is set to user
	function checkSessionUser() {
		session_start();
		if ($_SESSION['authority'] == constant('ACC_TYPE_USER')) {
			return True;
		}
		return False;
	}

?>