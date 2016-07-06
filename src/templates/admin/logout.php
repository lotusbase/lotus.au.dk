<?php
	// Start session
	session_start();
	
	// Unset the variables stored in session
	unset($_SESSION['user']);
	unset($_SESSION['user_id']);
	unset($_SESSION['user_first_name']);
	unset($_SESSION['user_last_name']);

	// Write session
	$_SESSION['logged_out'] = true;
	session_write_close();
	header("location: login.php");
	exit();
?>
