<?php

// Database connection
require_once('../config.php');
include_once('../functions.php');

if(!isset($_POST) || empty($_POST)) {
	$_SESSION['settings-err'] = "You have not submitted any information. Please try again.";
	session_write_close();
	header('location: settings.php');
	exit();
}

//Sanitize the POST values
$fname 		= $_POST['fname'];
$lname 		= $_POST['lname'];
$notify 	= $_POST['notify'];
$email 		= $_POST['email'];
$opassword 	= $_POST['opassword'];
$password 	= $_POST['password'];
$cpassword 	= $_POST['cpassword'];
$userid 	= $_POST['userid'];

// Retrieve user info
try {
	$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

	$user_query = $db->prepare("SELECT UserID, FirstName, LastName, Password, Verified, Activated FROM auth WHERE UserID = :userid");
	$user_query->bindParam(':userid', $userid);
	$user_query->execute();

	if($user_query->rowCount() > 0) {
		$user = $user_query->fetch(PDO::FETCH_ASSOC);
	} else {
		throw new Exception('no users returned');
	}

} catch(PDOException $e) {
	$_SESSION['settings-err'] = array($e->getMessage());
	session_write_close();
	header("location: settings.php");
} catch(Exception $e) {
	$_SESSION['settings-err'] = array($e->getMessage());
	session_write_close();
	header("location: settings.php");
}

// Define database query
$q = "UPDATE auth SET";
$q_params = array();

//Validation error flag
$error_flag = false;	

// Process input
if(!empty($fname)) {
	$q .= " FirstName = ?,";
	$q_params[] = $fname;
}
if(!empty($lname)) {
	$q .= " LastName = ?,";
	$q_params[] = $lname;
}
if($notify == 'y') {
	$q .= " Notify = 1,";
} else {
	$q .= " Notify = 0,";
}
if(!empty($email)) {
	$q .= " Email = ?,";
	$q_params[] = $email;
}
if(!empty($opassword) && !empty($password) && !empty($cpassword)) {
	if( strcmp($password, $cpassword) != 0 ) {
		$error_msg[] = 'passwords do not match';
		$error_flag = true;
	} else {
		// Check old password
		if (password_verify($opassword, $user['Password'])) {
			$q .= "Password = ?,";
			$q_params[] = password_hash($password, PASSWORD_DEFAULT);
			$pchange = true;
		} else {
			$error_msg[] = 'old password is incorrect';
			$error_flag = true;
		}
	}
}

// If there are input validations, redirect back to the registration form
if($error_flag) {
	$_SESSION['settings-err'] = $error_msg;
	session_write_close();
	header("location: settings.php");
	exit();
}

// Remove last comma
$q = substr($q, 0, -1);

// Update database
try {
	$update = $db->prepare($q." WHERE UserID = ?");
	$q_params[] = $user['UserID'];
	$update->execute($q_params);

	if($pchange) {
		$_SESSION['pass_changed'] = true;
		session_write_close();
		header('location: logout.php');
		exit();
	} else {
		$_SESSION['settings-ok'] = true;
		session_write_close();
		header('location: settings.php');
		exit();
	}

} catch(PDOException $e) {
	$error_msg[] = $e->getMessage();
	$_SESSION['settings-err'] = $error_msg;
	session_write_close();
	header("location: settings.php");
	exit();
}

?>