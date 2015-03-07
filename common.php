<?php

	function checkEmail($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	function hashPassword($password) {
		$salt = "4gfh21xdb231j54xdf51gbxgf8juser34";
		return sha1($salt . $password);
	}

?>