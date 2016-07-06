<?php

	// Include important files
	require_once('../config.php');

	// Error flag
	$errors = [];

	// Action
	$action = 0;

	// POST request: When the user submitted a request to reset their password
	if($_POST && isset($_POST['login']) && !empty($_POST['login'])) {

		// Get login
		$login = $_POST['login'];
		
		// Input Validations
		if($login == '') {
			$errors[] = 'Email has not been entered';
		}

		// If there are input validations, redirect back to the login form
		if($errors) {
			$_SESSION['reset_err'] = $errors[0];
			session_write_close();
			header("location: reset.php");
			exit();
		}

		// 
		try {

			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_ADMIN_USER, DB_ADMIN_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			$q1 = $db->prepare("SELECT * FROM auth WHERE Email = :email");
			$q1->bindParam(':email', $login);
			$q1->execute();

			if($q1->rowCount() < 1) {
				throw new PDOException('The email address does not exist in the system.');
			}

			// Fetch
			$d = $q1->fetch(PDO::FETCH_ASSOC);

			// Reject reset attempt is account is not activated or unverified
			if(!$d['Verified']) {
				$_SESSION['reset_err'] = 'Account has been registered but is not yet verified. Password reset refused. Please check your mailbox for the verification email.';
				session_write_close();
				header("location: reset.php");
				exit();
			}

			// Get salt
			$salt = $d['Salt'];

			// Define reset variables
			$reset_key = bin2hex(mcrypt_create_iv(11, MCRYPT_DEV_URANDOM));
			$reset_timestamp = date("Y-m-d H:i:s");

			// Write into database
			$reset_q_params = array($reset_key, $reset_timestamp, $login);
			$reset_q = $db->prepare("UPDATE auth SET ResetKey = ?, ResetTimestamp = ? WHERE Email = ?");
			$reset_q->execute($reset_q_params);

			// Send mail to user
			$mail = new PHPMailer(true);

			// Construct mail
			$mail_generator = new \LotusBase\MailGenerator();
			$mail_generator->set_title('<em>Lotus</em> Base: User account password reset');
			$mail_generator->set_header_image('cid:mail_header_image');
			$mail_generator->set_content(array(
				'<h3 style="text-align: center; ">Password reset for <em>Lotus</em> Base account</h3>
				<p>Hi '.$d["FirstName"].',</p>
				<p>Please click on the following link to reset your password&mdash;do this as soon as possible, because this link will expire withinin 24 hours.<br /><strong><a href="'.DOMAIN_NAME.WEB_ROOT.'/users/reset?email='.urlencode($login).'&amp;key='.urlencode($reset_key).'">'.DOMAIN_NAME.WEB_ROOT.'/users/reset?email='.urlencode($login).'&amp;key='.urlencode($reset_key).'</a></strong></p>
				<p>If you have received this email but do not recall requesting to reset your password, simply ignore and delete this email. <strong>Your account security has not been compromised.</strong></p>
				<p>Should you require any assistance, or have any enquiries, kindly contact the administration through the <a href="'.DOMAIN_NAME.WEB_ROOT.'/contact">contact form</a>. <strong>Do not reply to this email because mails to this account will not be directed to any staff.</strong></p>
				'));

			$mail->IsSMTP();
			$mail->IsHTML(true);
			$mail->Host			= SMTP_MAILSERVER;
			$mail->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
			$mail->CharSet		= "utf-8";
			$mail->Encoding		= "base64";
			$mail->Subject		= "Lotus Base: User account password reset";
			$mail->AltBody		= "To view the message, please use an HTML compatible email viewer.";
			$mail->MsgHTML($mail_generator->get_mail());
			$mail->AddAddress($login, $d['FirstName']." ".$d['LastName']);
			$mail->AddEmbeddedImage(DOC_ROOT."/dist/images/mail/header.jpg", mail_header_image);
			$mail->smtpConnect(
				array(
					"ssl" => array(
						"verify_peer" => false,
						"verify_peer_name" => false,
						"allow_self_signed" => true
					)
				)
			);

			$mail->Send();

			// Informs database that reset email has been sent
			$reset_email = $db->prepare("UPDATE auth SET ResetEmail = 1 WHERE Email = :email");
			$reset_email->bindParam(':email', $login);
			$reset_email->execute();

			// Write session
			$_SESSION['reset_pending'] = true;
			session_write_close();
			header("location: reset.php");
			exit();

		} catch(PDOException $e) {
			$_SESSION['reset_err'] = 'We have encountered an error with our database: '.$e->getMessage();
			session_write_close();
			header("location: reset.php");
			exit();
		} catch(phpmailerException $e) {
			$_SESSION['reset_err'] = 'We have encountered an error with sending you a reset link: '.$e->errorMessage();
			session_write_close();
			header("location: reset.php");
			exit();
		}
	}

	// POST request 2: user has provided new password
	else if($_POST && isset($_POST['action']) && $_POST['action'] === 'update') {

		// Get current URL
		$origin = urldecode($_POST['origin']);

		// Check if all fields are present
		if(!isset($_POST['password']) || !isset($_POST['cpassword']) || empty($_POST['password']) || empty($_POST['cpassword'])) {
			// Password fields are not present or empty
			$errors[] = 'You have not provided passwords.';
		}
		else if($_POST['password'] !== $_POST['cpassword']) {
			$errors[] = 'The two passwords you have provided do not match.';
		}
		else if(!isset($_POST['email']) || empty($_POST['email']) || !isset($_POST['key']) || empty($_POST['email'])) {
			$errors[] = 'Missing user identifiers.';
		}

		// If there are input validations, redirect back to the login form
		if($errors) {
			$_SESSION['reset_err'] = $errors[0];
			session_write_close();
			header("location: ".$origin);
			exit();
		}

		// Validate again
		// Check if reset key has already expired
		try {
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_ADMIN_USER, DB_ADMIN_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			$q3 = $db->prepare("SELECT * FROM auth WHERE Email = :email AND ResetKey = :resetkey LIMIT 1");
			$q3->bindParam(':email', $_POST['email']);
			$q3->bindParam(':resetkey', $_POST['key']);
			$q3->execute();

			$q3_r = $q3->fetch(PDO::FETCH_ASSOC);
			if(!$q3->rowCount()) {
				throw new Exception('Incorrect reset key and email combination.');
			}
			else if(strtotime($q3_r['ResetTimestamp']) < strtotime('-24 hours')) {
				throw new Exception('Your password reset token with a validity of 24 hours has expired. Please repeat the reset process.');
			}
			else if(!$q3_r['Verified']) {
				throw new Exception('Account has been registered but is not yet verified. Password reset refused. Please check your mailbox for the verification email.');
			}

			// Proceed with updating password
			$q4 = $db->prepare('UPDATE auth SET Password = ?, ResetKey = NULL WHERE Email = ? AND ResetKey = ?');
			$e4 = $q4->execute(array(
				password_hash($_POST['password'], PASSWORD_DEFAULT),
				$_POST['email'],
				$_POST['key']
				));

			if($e4) {
				$_SESSION['reset_done'] = true;
				session_write_close();
				header("location: ".$_SERVER['PHP_SELF']);
				exit();
			} else {
				throw new Exception('We are unable to update your password.');
			}

		} catch(PDOException $e) {
			$_SESSION['reset_err'] = 'We have encountered an error with our database: '.$e->getMessage();
			session_write_close();
			header("location: ".$origin);
			exit();
		} catch(Exception $e) {
			$_SESSION['reset_err'] = $e->getMessage();
			session_write_close();
			header("location: ".$origin);
			exit();
		}
	}

	// GET request: when the user clicks on the reset link in the email they have received
	else if($_GET) {
		// Catch errors
		if(!isset($_GET['email']) || !isset($_GET['key'])) {
			$_SESSION['reset_err'] = 'You have not provided an email and reset key combination.';
			session_write_close();
			header('location: reset.php');
			exit();
		}

		// Check if reset key has already expired
		try {
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_ADMIN_USER, DB_ADMIN_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			$q2 = $db->prepare("SELECT * FROM auth WHERE Email = :email AND ResetKey = :resetkey LIMIT 1");
			$q2->bindParam(':email', $_GET['email']);
			$q2->bindParam(':resetkey', $_GET['key']);
			$q2->execute();

			$q2_r = $q2->fetch(PDO::FETCH_ASSOC);
			if(!$q2->rowCount()) {
				throw new Exception('Incorrect reset key and email combination.');
			}
			else if(strtotime($q2_r['ResetTimestamp']) < strtotime('-24 hours')) {
				throw new Exception('Your password reset token with a validity of 24 hours has expired. Please repeat the reset process.');
			}
			else if(!$q2_r['Verified']) {
				throw new Exception('Account has been registered but is not yet verified. Password reset refused. Please check your mailbox for the verification email.');
			}
			else {
				// Set action
				$action = 1;
			}

		} catch(PDOException $e) {
			$_SESSION['reset_err'] = 'We have encountered an error with our database: '.$e->getMessage();
			session_write_close();
			header("location: reset.php");
			exit();
		} catch(Exception $e) {
			$_SESSION['reset_err'] = $e->getMessage();
			session_write_close();
			header("location: reset.php");
			exit();
		}

	}
?>
<!doctype html>
<html lang="en">
<head>
	<title>User account password recovery&mdash;Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/users.min.css" type="text/css" media="screen" />
</head>
<body class="users reset theme--white">

	<?php

		// Define header content
		$header_content = '';

		// Action 0: default state
		if($action === 0) {
			$header_content .= '<div class="align-center"><h1>Password recovery</h1><span class="byline">Forgotten your password?</span>';

			if(isset($_SESSION['reset_err'])) {
				$header_content .= '<p class="user-message warning"><span class="icon-attention"></span>'.$_SESSION['reset_err'].'</p>';
				unset($_SESSION['reset_err']);
			}
			else if(isset($_SESSION['reset_pending'])) {
				$header_content .= '<p class="user-message approved"><span class="icon-ok"></span>An email containing a password reset link has been sent. Please check your email.</p>';
				unset($_SESSION['reset_pending']);
			}
			else if(isset($_SESSION['reset_done'])) {
				$header_content .= '<p class="user-message approved"><span class="icon-ok"></span>Password successfully reset. You may proceed to <a href="'.WEB_ROOT.'/users/login">login</a>.</p>';
				unset($_SESSION['reset_done']);
			}
			else {
				$header_content .= '<p>Please enter your email. An email containing a reset link will be sent to your email address.</p>';
			}

			$header_content .= '</div><form id="reset-form" name="reset-form" method="POST" action="'.$_SERVER['PHP_SELF'].'">
				<div class="cols">
					<label class="col-one" for="login">Registered Email</label>
					<input class="col-two" type="text" name="login" id="login" placeholder="Registered Email" required />
					<button type="submit">Send reset request to email</button>
				</div>
			</form>';
		}
		// Action 1: user has provided a email+reset key combination
		else if($action === 1) {
			$header_content .= '<div class="align-center"><h1>Change password</h1>';

			if(isset($_SESSION['reset_err'])) {
				$header_content .= '<p class="user-message warning"><span class="icon-attention"></span>'.$_SESSION['reset_err'].'</p>';
				unset($_SESSION['reset_err']);
			}
			else if(isset($_SESSION['reset_success'])) {
				$header_content .= '<p class="user-message approved"><span class="icon-ok"></span>Your password has been successfully reset. We have logged you in, and you may continue using <em>Lotus</em> Base as an authenticated user.</p>';
				unset($_SESSION['reset_success']);
			}

			$header_content .= '</div><form id="reset-form" name="reset-form" method="POST" action="'.$_SERVER['PHP_SELF'].'">
				<div class="cols">
					<label class="col-one" for="password">New password</label>
					<input class="col-two" type="password" name="password" id="password" placeholder="Your new password" required />

					<label class="col-one" for="cpassword">Confirm password</label>
					<input class="col-two" type="password" name="cpassword" id="cpassword" placeholder="Confirm new password" required />

					<input type="hidden" name="email" value="'.$_GET['email'].'" />
					<input type="hidden" name="key" value="'.$_GET['key'].'" />

					<input type="hidden" name="origin" value="'.urlencode($_SERVER['REQUEST_URI']).'" />

					<input type="hidden" name="action" value="update" />
					<button type="submit">Update password</button>
				</div>
			</form>';
		}

		// Generate header
		$header = new \LotusBase\PageHeader();
		$header->set_header_content($header_content);
		echo $header->get_header();
	?>

	<?php include(DOC_ROOT.'/footer.php'); ?>

</body>
</html>
