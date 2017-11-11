<?php

	// Include
	require_once('../config.php');
	include_once('../functions.php');

	// Connect to database
	try {
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_ADMIN_USER, DB_ADMIN_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	} catch(PDOException $e) {
		$_SESSION['reset_err'] = 'Unable to connect to database.';
		session_write_close();
		header('location: reset.php');
		exit();
	}

	// Check if user has followed a valid link
	if(!$_GET) {
		$_SESSION['reset_err'] = 'You have used an invalid reset token, or have tried to access a secured page directly.';
		session_write_close();
		header('location: reset.php');
		exit();
	}

	// Check if user has already submitted a form
	if(isset($_POST) && !empty($_POST)) {

		// Validation error flag
		$error_flag = false;

		// Get user input
		$reset_key = $_POST['key'];
		$email = $_POST['email'];
		$password = $_POST['password'];
		$cpassword = $_POST['cpassword'];

		// Input Validations
		if(empty($reset_key) || empty($email)) {
			$error_flag = true;
			$error_message = 'Incorrect identification token has been presented.';
		}
		if($password != $cpassword) {
			$error_flag = true;
			$error_message = 'Passwords are not identical.';
		}
		
		// If there are input validations, redirect back to the login form
		if($error_flag) {
			$_SESSION['newpass_err'] = $error_message;
			session_write_close();
			header("location: newpass.php?email=$email&key=$reset_key");
			exit();
		}

		try {
			$q1 = $db->prepare("SELECT * FROM auth WHERE Email = :email AND ResetKey = :resetkey");
			$q1->bindParam(':email', $email);
			$q1->bindParam(':resetkey', $reset_key);
			$q1->execute();

			$user = $q1->fetch(PDO::FETCH_ASSOC);
			$reset_time = strtotime($user['ResetTimestamp']);
			$current = strtotime(date('Y-m-d H:i:s'));

			if($current - $reset_time > (24 * 60 * 60)) {
				// If token is more than 24 hours old, prevent password reset
				$q2 = $db->prepare("UPDATE auth SET ResetTimestamp = NULL, ResetEmail = 0, ResetKey = NULL WHERE Email = :email AND ResetKey = :resetkey");
				$q2->bindParam(':email', $email);
				$q2->bindParam(':resetkey', $resetkey);
				$q2->execute();

				$_SESSION['reset_err'] = 'Your password reset token with a validity of 24 hours has expired. Please repeat the reset process.';
				session_write_close();
				header("location: reset.php");
				exit();
			} else {
				// Check if user is verified AND activated
				if($user['Verified'] == 1 && $user['Activated'] == 1) {

					// Create new salt
					$salt = bin2hex(mcrypt_create_iv(11, MCRYPT_DEV_URANDOM));

					// Create new hashed password
					$password = password_hash($password, PASSWORD_DEFAULT);	
					
					session_regenerate_id();
					$_SESSION['sess_member_id'] = $user['UserID'];
					$_SESSION['sess_first_name'] = $user['FirstName'];
					$_SESSION['sess_last_name'] = $user['LastName'];
					$_SESSION['newpass_success'] = true;
					session_write_close();

					// Write new salted and hashed password, and remove password reset token
					$q3 = $db->prepare("UPDATE auth SET Password = ?, ResetKey = NULL, ResetEmail = 0 WHERE Email = ? AND ResetKey = ?");
					$q3->execute(array($password, $email, $reset_key));

					// Get redirection url if any
					header("location: index.php");
					exit();

				} else {
					// User has not verified account yet
					$q3 = $db->prepare("UPDATE auth SET ResetKey = NULL, ResetEmail = 0, ResetTimestamp = NULL WHERE Email = :email AND ResetKey = :resetkey");
					$q3->bindParam(':email', $email);
					$q3->bindParam(':resetkey', $reset_key);
					$q3->execute();

					if($user['Verified'] == 0) {
						$_SESSION['newpass_err'] = "<strong>Account not yet verified</strong><br />You have not verified your account. Password reset is unauthorized.";
					} else {
						$_SESSION['newpass_err'] = "<strong>Account has been verified but awaiting administration approval.</strong><br />Password reset is unauthorized.";
					}
					
					session_write_close();
					header("location: newpass.php?email=$email&key=$reset_key");
					exit();
				}
			}

		} catch(PDOException $e) {
			$_SESSION['newpass_err'] = $e->getMessage();
			session_write_close();
			header("location: newpass.php?email=$email&key=$reset_key");
			exit();
		}


	}

	// Get variables
	if(isset($_GET['email']) && isset($_GET['key'])) {
		$email = $_GET['email'];
		$reset_key = $_GET['key'];
	}

?>
<!doctype html>
<html lang="en">
<head>
	<title>New Password &mdash; LORE1 Resource Site</title>
	<?php include('head.php') ?>
</head>
<body class="floating newpass">
	<form id="newpass-form" name="newpass-form" method="post" action="#">
		<?php
		try {
			$q1 = $db->prepare("SELECT * FROM auth WHERE Email = :email AND ResetKey = :resetkey AND ResetEmail = 1");
			$q1->bindParam(':email', $email);
			$q1->bindParam(':resetkey', $reset_key);
			$q1->execute();

			if($q1->rowCount() !== 1) {
				throw new Exception('Invalid email and reset key combination.');
			}

			$user = $q1->fetch(PDO::FETCH_ASSOC);
			$reset_time = strtotime($user['ResetTimestamp']);
			$current = strtotime(date('Y-m-d H:i:s'));

			// Ensure that request has not expired
			if($current - $reset_time > (24 * 60 * 60)) {
				// If token is more than 24 hours old, prevent password reset
				$q2 = $db->prepare("UPDATE auth SET ResetTimestamp = NULL, ResetEmail = 0, ResetKey = NULL WHERE Email = :email AND ResetKey = :resetkey");
				$q2->bindParam(':email', $email);
				$q2->bindParam(':resetkey', $reset_key);
				$q2->execute();

				$_SESSION['reset_err'] = 'Your password reset token with a validity of 24 hours and issued at '.$user['ResetTimestamp'].' has expired. Please repeat the reset process.';
				session_write_close();
				header("location: reset.php");
				exit();
			} else {
				echo '<h1>New Password</h1><p>For LORE1 administrator account registered with: <strong>'.htmlentities($_GET['email']).'</strong></p>';

				// Display error messages if any
				if(isset($_SESSION['newpass_err'])) {
					echo '<p class="user-message warning">'.$_SESSION['newpass_err'].'</p>';
					unset($_SESSION['newpass_err']);
				}

				?>
		<p class="user-message note">You have arrived at this page because you have a valid token that allows you to reset your password.</p>

		<label for="password">New Password</label>
		<input type="password" name="password" id="password" placeholder="New Password" />

		<label for="cpassword">Retype New Password</label>
		<input type="password" name="cpassword" id="cpassword" placeholder="Retype New Password" />

		<input type="hidden" name="key" value="<?php echo $reset_key; ?>" />
		<input type="hidden" name="email" value="<?php echo $email; ?>" />
		<input type="submit" name="Submit" value="Set New Password" />
				<?php
			}

		} catch(PDOException $e) {
			$_SESSION['reset_err'] = $e->getMessage();
			session_write_close();
			header('location: reset.php');
			exit();
		} catch(Exception $e) {
			echo '<h1>Invalid Reset Token</h1><p class="user-message warning">You have presented an invalid reset token. It has either expired, or you have copied the URL incorrectly. If this problem persists, please contact the system administrator.</p>';
		}


		?>
		
	</form>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.14.0/jquery.validate.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/prefixfree/1.0.7/prefixfree.min.js"></script>
	<script src="./includes/functions.min.js"></script>
</body>
</html>
