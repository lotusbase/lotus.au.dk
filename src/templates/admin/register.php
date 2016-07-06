<?php

	//Include database connection details
	require_once('../config.php');
	include_once('../functions.php');
	require_once('../vendor/phpmailer/PHPMailerAutoload.php');

	// Check if user has submitted a form
	if(isset($_POST) && !empty($_POST)) {
		// Array to store validation errors
		$errmsg_arr = array();
		
		// Validation error flag
		$errflag = false;

		// Store user inputs
		$user_input = array('FirstName' => $fname, 'LastName' => $lname, 'Email' => $email, 'Login' => $login);
		
		//Connect to mysql server
		try {
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_ADMIN_USER, DB_ADMIN_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			// Retrieve POST values
			$fname = $_POST['fname'];
			$lname = $_POST['lname'];
			$email = $_POST['email'];
			$login = $_POST['login'];
			$password = $_POST['password'];
			$cpassword = $_POST['cpassword'];
			$captcha = $_POST['g-recaptcha-response'];
			
			//Input Validations
			if($fname == '') {
				$errmsg_arr[] = 'first name missing';
				$errflag = true;
			}
			if($lname == '') {
				$errmsg_arr[] = 'last name missing';
				$errflag = true;
			}
			if($email == '') {
				$errmsg_arr[] = 'email missing';
				$errflag = true;
			}
			if($login == '') {
				$errmsg_arr[] = 'username missing';
				$errflag = true;
			}
			if($password == '') {
				$errmsg_arr[] = 'password missing';
				$errflag = true;
			}
			if($cpassword == '') {
				$errmsg_arr[] = 'confirm password missing';
				$errflag = true;
			}
			if(strcmp($password, $cpassword) != 0) {
				$errmsg_arr[] = 'passwords do not match';
				$errflag = true;
			}
			if($captcha == '') {
				$errmsg_arr[] = 'captcha failed';
				$errflag = true;
			}

			if($errflag) {
				$_SESSION['reg_err'] = $errmsg_arr;
				$_SESSION['reg_input'] = $user_input;
				session_write_close();
				header("location: register.php");
				exit();
			}

			// Check for duplicate login ID
			$q1 = $db->prepare("SELECT * FROM auth WHERE username = :username OR email= :email");
			$q1->bindParam(':username', $login);
			$q1->bindParam(':email', $email);
			$q1->execute();

			if($q1->rowCount() > 0) {
				$_SESSION['reg_err'] = array('user already exists');
				$_SESSION['reg_input'] = $user_input;
				session_write_close();
				header("location: register.php");
				exit();
			}

			// Proceed with generating new user
			$password = password_hash($password, PASSWORD_DEFAULT);
			$new_user_params = array($fname, $lname, $email, $login, $password);
			$new_user = $db->prepare("INSERT INTO auth(FirstName,LastName,Email,Username,Password) VALUES(".str_repeat('?,', count($new_user_params)-1)."?)");
			$new_user->execute($new_user_params);

			// Send mail
			$mail = new PHPMailer(true);
			$body = '';
			$body	.= '<body style="color: #555; font-family: Arial, Helvetica, sans-serif; margin: 12px; padding: 12px;">';
			$body	.= '<table style="background-color: #eee; border: 0; line-height: 21px; font-size: 14px;" cellspacing="0" cellpadding="0" width="640">';
			$body	.= '<tr>';
			$body	.= '<td style="padding: 14px;" width="48"><a href="https://'.$_SERVER['HTTP_HOST'].'/" title="Lotus Base"><img src="data/mail/branding.gif" alt="Lotus Base logo"></a></td>';
			$body	.= '<td style="padding: 14px;"><h1 style="color: #444; font-size: 28px; margin: 0; text-align: center;">Account Registration Verification</h1></td>';
			$body	.= '</tr>';
			$body	.= '<tr>';
			$body	.= '<td colspan="2" style="background-color: #aaa;" height="1"></td>';
			$body	.= '</tr>';
			$body	.= '<tr>';
			$body	.= '<td colspan="2" style="background-color: #fff;" height="1"></td>';
			$body	.= '</tr>';
			$body	.= '<tr>';
			$body	.= '<td colspan="2" style="background-color: transparent;" height="10"></td>';
			$body	.= '</tr>';
			$body	.= '<tr>';
			$body	.= '<td style="padding: 14px;" colspan="2">';
			$body	.= '<strong>Dear '.$fname.',</strong>';
			$body	.= '<br ><br />';
			$body	.= 'Thank you for registering with us. Please click on the following link to verify your account:';
			$body	.= '<br /><br />';
			$body	.= '<strong><a href="https://'.$_SERVER['HTTP_HOST'].'/admin/verify.php?email='.urlencode($email).'" style="color: #4a7298;">https://'.$_SERVER['HTTP_HOST'].'/admin/verify.php?email='.urlencode($email).'&amp;key='.urlencode($salt).'</a></strong>';
			$body	.= '<br /><br />';
			$body	.= '<strong>Account Information:</strong><br />';
			$body	.= 'Username: '.$login;
			$body 	.= '<br /><br />';
			$body 	.= '<strong>Please note that your account is subjected to activation by the system administration.</strong> You will be notified when your account has been activated.';
			$body	.= '</td>';
			$body	.= '</tr>';
			$body	.= '<tr>';
			$body	.= '<td style="padding: 14px;" colspan="2">';
			$body	.= 'Should you require any assistance, or have any enquiries, kindly contact the administration through the <a href="https://'.$_SERVER['HTTP_HOST'].'/contact.php" style="color: #4a7298;">contact form</a>. <strong>Do not reply to this email because mails to this account will not be directed to any staff.</strong>';
			$body	.= '<br /><br /><br />';
			$body	.= 'Yours sincerely,<br /><em>Lotus</em> Base Project Team<br />Centre for Carbohydrate Recognition and Signalling<br />Aarhus University<br />Gustav Wieds Vej 10<br />DK-8000 Aarhus C';
			$body	.= '</td>';
			$body	.= '</tr>';
			$body	.= '</table>';
			$body	.= '</body>';

			$mail->IsSMTP(); // telling the class to use SMTP
			$mail->Host       = SMTP_MAILSERVER; // SMTP server
			$mail->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
			$mail->IsHTML(true);
			$mail->CharSet    = "utf-8";
			$mail->Encoding   = "base64";
			$mail->Subject    = "Lotus Base Account Registration";
			$mail->AltBody    = "To view the message, please use an HTML compatible email viewer."; // optional, comment out and test
			$mail->MsgHTML($body);
			$mail->AddAddress($email, $fname." ".$lname);
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

			// Send mail
			$mail->Send();

			// Success
			$_SESSION['reg_success'] = true;
			session_write_close();
			header("location: register.php");
			exit();

		} catch(PDOException $e) {
			$_SESSION['reg_err'] = array($e->getMessage());
			$_SESSION['reg_input'] = $user_input;
			session_write_close();
			header("location: register.php");
			exit();
		} catch(phpmailerException $e) {
			$_SESSION['reg_err'] = array($e->errorMessage());
			$_SESSION['reg_input'] = $user_input;
			session_write_close();
			header("location: register.php");
			exit();
		}
	}
?>
<!doctype html>
<html lang="en">
<head>
	<title>LORE1 Resource &mdash; New User Registration</title>
	<?php include('head.php') ?>
</head>
<body class="admin floating register">
	<form id="register-form" name="register-form" method="post" action="#">
		<h1>New User Registration</h1>

		<?php
			if(isset($_SESSION['reg_err']) && is_array($_SESSION['reg_err']) && count($_SESSION['reg_err']) > 0) {
				$msg ='<p class="user-message warning"><strong>There seems to be an issue with your details:</strong> ';
				foreach($_SESSION['reg_err'] as $err) {
					$msg .= $err.", ";
				}
				$msg = substr($msg, 0, -2);
				$msg .= '.</p>';
				echo $msg;
				unset($_SESSION['reg_err']);
			}
			if(isset($_SESSION['reg_input'])) {
				$user_input = $_SESSION['reg_input'];
				unset($_SESSION['reg_input']);
			} else {
				$user_input = array('','','','');
			}
			if(isset($_SESSION['reg_success'])) { ?>
		<p class="user-message approved"><strong>Registration successful.</strong> We have sent you a verfication email. Please verify your account with the link in the email before you log in.</p>
				<?php unset($_SESSION['reg_success']);
			}
		?>

		<div class="way-wrap">
			<div class="three-way">
				<label for="fname">First Name</label>
				<input type="text" name="fname" id="fname" placeholder="First Name" tabindex="1" value="<?php echo $user_input['FirstName']; ?>" />
			</div>
			<div class="three-way">
				<label for="lname">Last Name</label>
				<input type="text" name="lname" id="lname" placeholder="Last Name" tabindex="2" value="<?php echo $user_input['LastName']; ?>" />
			</div>
			<div class="three-way">
				<label for="email">Email</label>
				<input type="email" name="email" id="email" placeholder="Email" tabindex="3" value="<?php echo $user_input['Email']; ?>" />
			</div>
		</div>

		<div class="separator"></div>

		<div class="way-wrap">
			<div class="three-way">
				<label for="login">Username</label>
				<input type="text" name="login" id="login" placeholder="Username" tabindex="4" value="<?php echo $user_input['Login']; ?>" />
			</div>
			<div class="three-way">
				<label for="password">Password</label>
				<input type="password" name="password" id="password" placeholder="Password" tabindex="5" />
			</div>
			<div class="three-way">
				<label for="cpassword">Retype Password</label>
				<input type="password" name="cpassword" id="cpassword" placeholder="Repeat Password" tabindex="6" />
			</div>
		</div>

		<div class="separator"></div>

		<div class="way-wrap">
			<div class="two-way"><div id="google-recaptcha"></div></div>
			<div class="two-way">
				<input type="submit" name="Register" value="Register" />
			</div>
		</div>
	</form>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.14.0/jquery.validate.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/prefixfree/1.0.7/prefixfree.min.js"></script>
	<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&amp;render=explicit" async defer></script>
	<script src="../admin/includes/functions.min.js"></script>
	<script>
		var onloadCallback = function() {
				grecaptcha.render('google-recaptcha', {
					'sitekey' : '6Ld3VQ8TAAAAAO7VZkD4zFAvqUxUuox5VN7ztwM5',
					'callback': verifyCallback,
					'expired-callback': expiredCallback,
					'tabindex': 7
				});
			},
			verifyCallback = function(response) {
				$('form :input[type="submit"]')
					.removeClass('disabled')
					.prop('disabled', false);
			},
			expiredCallback = function() {
				grecaptcha.reset();
			};
		$(function() {
			$('form :input[type="submit"]')
				.addClass('disabled')
				.prop('disabled', true);
		});
	</script>
</body>
</html>
