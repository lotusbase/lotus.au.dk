<?php

	//Include database connection details
	require_once('../config.php');
	require_once('../functions.php');
	require_once('../vendor/phpmailer/PHPMailerAutoload.php');

	if(isset($_POST) && !empty($_POST)) {
		// Validation error flag
		$errflag = false;

		// Get login
		$login = $_POST['login'];
		
		// Input Validations
		if($login == '') {
			$errmsg = 'Email has not been entered';
			$errflag = true;
		}

		// If there are input validations, redirect back to the login form
		if($errflag) {
			$_SESSION['reset_err'] = $errmsg;
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
			if($d['Verified'] == 0) {
				$_SESSION['reset_err'] = 'Account has been registered but is not yet verified. Password reset refused. Please check your mailbox for the verification email.';
				session_write_close();
				header("location: reset.php");
				exit();
			} elseif($d['Activated'] == 0) {
				$_SESSION['reset_err'] = 'Account is awaiting admin approval. Password reset refused. Please await administrative approval.';
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

			// Send mail
			$mail = new PHPMailer(true);

			$body = "";
			$body	.= "<body style=\"color: #555; font-family: Arial, Helvetica, sans-serif; margin: 12px; padding: 12px;\">";
			$body	.= "<table style=\"background-color: #eee; border: 0; line-height: 21px; font-size: 14px;\" cellspacing=\"0\" cellpadding=\"0\" width=\"640\">";
			$body	.= "<tr>";
			$body	.= "<td style=\"padding: 14px;\" width=\"48\"><img src=\"data/mail/branding.gif\" alt=\"LORE1 Logo\"></td>\n";
			$body	.= "<td style=\"padding: 14px;\"><h1 style=\"color: #444; font-size: 28px; margin: 0; text-align: center;\">Password Reset</h1></td>\n";
			$body	.= "</tr>\n";
			$body	.= "<tr>";
			$body	.= "<td colspan=\"2\" style=\"background-color: #aaa;\" height=\"1\"></td>\n";
			$body	.= "</tr>\n";
			$body	.= "<tr>";
			$body	.= "<td colspan=\"2\" style=\"background-color: #fff;\" height=\"1\"></td>\n";
			$body	.= "</tr>\n";
			$body	.= "<tr>";
			$body	.= "<td colspan=\"2\" style=\"background-color: transparent;\" height=\"10\"></td>\n";
			$body	.= "</tr>\n";
			$body	.= "<tr>";
			$body	.= "<td style=\"padding: 14px;\" colspan=\"2\">";
			$body	.= "<strong>Dear ".$d["FirstName"].",</strong>";
			$body	.= "<br ><br />";
			$body	.= "Please click on the following link to reset your password. Do this as soon as possible, because this link will expire in 24 hours.";
			$body	.= "<br /><br />";
			$body	.= "<strong><a href=\"https://lotus.au.dk/admin/newpass.php?email=".urlencode($login)."&amp;key=".urlencode($reset_key)."\" style=\"color: #4a7298;\">https://lotus.au.dk/admin/newpass.php?email=".urlencode($login)."&amp;key=".urlencode($reset_key)."</a></strong>";
			$body 	.= "<br /><br />";
			$body 	.= "If you have received this email without resetting your password, simply ignore and delete this email. Your account security has not been compromised.";
			$body	.= "</td>\n";
			$body	.= "</tr>\n";
			$body	.= "<tr>";
			$body	.= "<td style=\"padding: 14px;\" colspan=\"2\">";
			$body	.= "Should you require any assistance, or have any enquiries, kindly contact the administration through the <a href=\"https://lotus.au.dk/contact.php\" style=\"color: #4a7298;\">contact form</a>. <strong>Do not reply to this email because mails to this account will not be directed to any staff.</strong>";
			$body	.= "<br /><br /><br />";
			$body	.= "Yours sincerely,<br /><em>Lotus Base</em> Project Team<br />Centre for Carbohydrate Recognition and Signalling<br />Aarhus University<br />Gustav Wieds Vej 10<br />DK-8000 Aarhus C";
			$body	.= "</td>\n";
			$body	.= "</tr>\n";
			$body	.= "</table>\n";
			$body	.= "</body>";

			$mail->IsSMTP(); // telling the class to use SMTP
			$mail->IsHTML();
			$mail->Host       = SMTP_MAILSERVER; // SMTP server
			$mail->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
			$mail->CharSet    = "utf-8";
			$mail->Subject    = "Lotus Base Admin Password Reset";
			$mail->AltBody    = "To view the message, please use an HTML compatible email viewer."; // optional, comment out and test
			$mail->MsgHTML($body);
			$mail->AddAddress($login, $d['FirstName']." ".$d['LastName']);
			$mail->AddAttachment("data/mail/branding.gif");      // attachment
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
			$_SESSION['reset_success'] = true;
			session_write_close();
			header("location: reset.php");
			exit();

		} catch(PDOException $e) {
			$_SESSION['reset_err'] = $e->getMessage();
			session_write_close();
			header("location: reset.php");
			exit();
		} catch(phpmailerException $e) {
			$_SESSION['reset_err'] = $e->errorMessage();
			session_write_close();
			header("location: reset.php");
			exit();
		}
	}
?>
<!doctype html>
<html lang="en">
<head>
	<title>Password Reset &mdash; LORE1 Resource Site</title>
	<?php include('head.php') ?>
</head>
<body class="floating reset">
	<form id="reset-form" name="reset-form" method="POST" action="#">
		<h1>Password Reset</h1>

		<?php if(isset($_SESSION['reset_err'])) { ?>
		<p class="user-message warning"><?php echo $_SESSION['reset_err']; ?></p>
		<?php unset($_SESSION['reset_err']);
		}

		if(isset($_SESSION['reset_success'])) { ?>
		<p class="user-message approved">Verification email containing a password reset link has been sent. Please check your email.</p>
		<?php unset($_SESSION['reset_success']);
		} else { ?>
		<p class="user-message instruction">Please enter your email. A confirmation email will be sent to your email address.</p>
		<?php } ?>

		<label for="login">Registered Email</label>
		<input type="text" name="login" id="login" placeholder="Registered Email" />

		<input type="submit" name="Submit" value="Send Reset Link" /><a class="back-link" href="javascript:history.go(-1)">&laquo; Back</a>
	</form>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
	<script src="../js/jquery.validate.min.js"></script>
	<script src="../js/prefixfree.min.js"></script>
	<script src="../admin/includes/functions.js"></script>
</body>
</html>
