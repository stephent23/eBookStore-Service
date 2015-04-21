<?php

	require_once 'config.php';

	session_start();

	/**
	 * Checks that the username session is set, indicating that a user has been authenticated.
	 * @return boolean True if user has been authenticated and false otherwise.
	 */
	function isLoggedIn() {
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
		if ($_SESSION['authority'] == constant('ACC_TYPE_USER')) {
			return True;
		}
		return False;
	}

	/**
	 * Returns the logged in users username as set in the session variable 'username'
	 * @return String The username of the logged in user
	 */
	function getSessionUsername() {
		return $_SESSION['username'];
	}

?>