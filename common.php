<?php

	require 'config.php';

	/**
	 * Checks that the username session is set, indicating that a user has been authenticated.
	 * @return boolean True if user has been authenticated and false otherwise.
	 */
	function isLoggedIn() {
		session_start();
		if(isset($_SESSION['username'])) {
			return True;
		}
		return False;
	}

	/**
	 * Checks that the authority session is set to admin.
	 * @return boolean True if user is an admin and false otherwise.
	 */
	function checkSessionAdmin() {
		session_start();
		if ($_SESSION['authority'] == constant('ACC_TYPE_ADMIN')) {
			return True;
		}
		return False;
	}

	/**
	 * Checks that the authority session is set to user.
	 * @return boolean True if user is a user (Account type) and false otherwise.
	 */
	function checkSessionUser() {
		session_start();
		if ($_SESSION['authority'] == constant('ACC_TYPE_USER')) {
			return True;
		}
		return False;
	}

?>