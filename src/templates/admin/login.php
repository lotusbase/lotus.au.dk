<?php
	// Load important files
	require_once('../config.php');

	// Redirect to profile page if user is already logged in
	if(isset($_SESSION['user_id']) && !empty(trim($_SESSION['user_id']))) {
		header('location: '.DOC_ROOT.'/users/profile');
	}

	// Check login attempt
	if(isset($_POST) && isset($_POST['login']) && isset($_POST['password'])) {
		if(empty($_POST['login'])) {
			$login_errors[] = 'Missing username.';
		}
		if(empty($_POST['password'])) {
			$login_errors[] = 'Missing password.';
		}

		if(isset($login_errors) && count($login_errors) > 0) {
			$_SESSION['login_error'] = $login_errors;
			session_write_close();
			header("location: login.php");
			exit();
		}

		$login = $_POST['login'];
		$password = $_POST['password'];

		try {
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			$q1 = $db->prepare("SELECT * FROM auth WHERE Username = :username OR Email = :email");
			$q1->bindParam(':username', $login);
			$q1->bindParam(':email', $login);
			$q1->execute();

			if($q1->rowCount() > 0) {
				$row = $q1->fetch(PDO::FETCH_ASSOC);

				// Check password
				if (password_verify($password, $row['Password'])) {

					// Create one-time session key
					$sessionkey = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));

					// Start session time
					$_SESSION['created'] = time();

					// Check account status
					if($row['Verified'] == 1 && $row['Activated'] == 1) {
						session_regenerate_id();
						$_SESSION['user'] = array_intersect_key($row, array_flip(array(
							'FirstName',
							'LastName',
							'Email',
							'Organization',
							'UserID',
							'Authority',
							'Verified',
							'Activated'
							)));
						$_SESSION['user']['SessionKey'] = $sessionkey;
						session_write_close();

						// Perform second query
						// 1. Remove password reset token (user has remembered password)
						// 2. Set session key
						$q2 = $db->prepare("UPDATE auth SET ResetKey = NULL, SessionKey = :sessionkey WHERE Username = :username OR Email = :email");
						$q2->bindParam(':username', $login);
						$q2->bindParam(':sessionkey', $sessionkey);
						$q2->bindParam(':email', $login);
						$q2->execute();

						// Get redirection url if any
						if(!empty($_POST['redir'])) {
							header("location: ".urldecode($_POST['redir']));
						} else {
							header("location: index.php");
						}
						exit();

					} elseif($row['Verified'] == 0) {
						throw new Exception('Account not yet verified. Please check your email for verification link.');
					} else {
						throw new Exception('Account awaiting administrative approval.');
					}
				} else {
					// Invalid password
					throw new Exception('Username and password combination is invalid.');
				}
			} else {
				throw new Exception('Username and password combination is invalid.');
			}

		} catch(PDOException $e) {
			$_SESSION['user_login_error'] = array('Unable to connect to database.');
			session_write_close();
			header("location: login.php");
			exit();
		} catch(Exception $e) {
			$_SESSION['user_login_error'] = array($e->getMessage());
			session_write_close();
			header("location: login.php");
			exit();
		}
	}
?>
<!doctype html>
<html lang="en">
<head>
	<title>User Login&mdash;Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/users.min.css" type="text/css" media="screen" />
</head>
<body class="users login theme--white">
	<?php

		// Login form
		$login_form = '<div class="align-center">
			<h1>Sign in</h1>
			<span class="byline">Not a user yet? <a href="'.WEB_ROOT.'/users/register" title="Register new user">Register a new account here</a>.</span>
		';

		if(isset($_SESSION['user_login_error'])) {
			$login_form .= '<p class="user-message warning">'.implode(' ', $_SESSION['user_login_error']).'</p>';
			unset($_SESSION['user_login_error']);
		} else if(isset($_SESSION['user_logged_out'])) {
			$login_form .= '<p><span class="icon-ok"></span>You have successfully logged out.</p>';
			unset($_SESSION['user_logged_out']);
		} else if(isset($_SESSION['user_pass_changed'])) {
			$login_form .= '<p><span class="icon-ok"></span>You have successfully changed your password. Please log in with your new password.</p>';
			unset($_SESSION['user_pass_changed']);
		} else {
			$login_form .= '<p>Log in to your <em>Lotus</em> Base account for more convenient access to various tools.</p>';
		}

		$login_form .= '</div><form id="login-form" method="post" action="#">
			<div class="cols">
				<label class="col-one" for="login">Username / email</label>
				<input class="col-two" type="text" name="login" id="login" placeholder="Username or email" autofocus tabindex="1" />

				<label class="col-one" for="password">Password</label>
				<div class="col-two">
					<input type="password" name="password" id="password" placeholder="Password" tabindex="2" />
					<a href="./reset" title="Reset password">Forgot password?</a>
				</div>

				<label class="col-one" for="remember-login"></label>
				<div class="col-two">
					<label for="remember-login"><input type="checkbox" id="remember-login" name="remember_login" /><span>Remember me for one week</span></label>
				</div>

				<input type="hidden" name="redir" value="'.(isset($_GET['redir']) ? htmlspecialchars($_GET['redir']) : '').'">

				<button type="submit" tabindex="3">Login</button>
			</div>
		</form>';

		// Generate header
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_theme('white');
		$header->set_header_content($login_form);
		echo $header->get_header();
	?>

	<?php include(DOC_ROOT.'/footer.php'); ?>
</body>
</html>