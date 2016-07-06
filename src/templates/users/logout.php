<?php
	// Start session
	session_start();

	// Unset cookie used to store JWT
	setcookie('auth_token', '', time()-60, '/');

	// Write session
	$_SESSION['user_logged_out'] = true;
	session_write_close();
	if(!empty($_GET['redir'])) {
		header("location: ".urldecode($_GET['redir']));
	} else {
		header("location: ../");
	}
	exit();
?>
