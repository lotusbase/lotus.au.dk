<?php

	// Get important files
	require_once('../config.php');

	// Mailchimp API
	use \DrewM\MailChimp\MailChimp;

	// Use JWT
	use \Firebase\JWT\JWT;

	// Error flag
	$error = array();
	$status = false;

	// Get variables
	if(isset($_GET['email']) && isset($_GET['key']) && !empty($_GET['email']) && !empty($_GET['key'])) {
		$email				= $_GET['email'];
		$verificationkey	= $_GET['key'];

		try {
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_ADMIN_USER, DB_ADMIN_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			$q1 = $db->prepare("SELECT * FROM auth WHERE Email = :email AND VerificationKey = :verificationkey LIMIT 1");
			$q1->bindParam(':email', $email);
			$q1->bindParam(':verificationkey', $verificationkey);
			$q1->execute();

			// Throw error for invalid email and/or verification key
			if($q1->rowCount() < 1) {
				throw new Exception('Incorrect email and verifcation key combination. Please check that you have followed the correct link in the membership verification email we have sent you.');
			}

			// Get user details
			$user = $q1->fetch(PDO::FETCH_ASSOC);

			// Throw error if verification key has expired
			if(strtotime($user['VerificationKeyTimestamp']) <= strtotime('-24 hours')) {
				throw new Exception('Your verification key has expired.');
			}

			// Exit if user has already been verified
			if(intval($user['Verified']) === 1) {
				// Redirect to profile page
				header('Location: ./');
				exit();
			}

			// Update database, set user to verified
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_ADMIN_USER, DB_ADMIN_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			$q2 = $db->prepare("UPDATE auth SET Verified = 1 WHERE Email = :email AND VerificationKey = :verificationkey");
			$q2->bindParam(':email', $email);
			$q2->bindParam(':verificationkey', $verificationkey);
			$q2->execute();

			$status = true;

			// Subscribe verified user to mailing list
			if($user['MailingList']) {
				$MailChimp = new MailChimp(MAILCHIMP_API_KEY);
				$MailChimp_subscribe = $MailChimp->post("lists/c469e14ec3/members", [
					'email_address' => $user['Email'],
					'status'		=> 'subscribed',
					'merge_fields'	=> [
						'FNAME' => $user['FirstName'],
						'LNAME' => $user['LastName']
					]
				]);
			}

			// Send mail to user
			$mail = new PHPMailer(true);

			// Construct mail
			$mail_generator = new \LotusBase\MailGenerator();
			$mail_generator->set_title('<em>Lotus</em> Base: New user registration');
			$mail_generator->set_header_image('cid:mail_header_image');
			$mail_generator->set_content(array(
				'<h3 style="text-align: center; ">New user account verified</h3>
				<p>A user with the email <strong>'.$email.'</strong> '.(isset($user['Organization']) ? 'from <strong>'.$user['Organization'].'</strong> ' : '').'have signed up and verified their account.</p>
				'));

			$mail->IsSMTP();
			$mail->IsHTML(true);
			$mail->Host			= SMTP_MAILSERVER;
			$mail->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
			$mail->CharSet		= "utf-8";
			$mail->Encoding		= "base64";
			$mail->Subject		= "Lotus Base: New user account verified";
			$mail->AltBody		= "To view the message, please use an HTML compatible email viewer.";
			$mail->MsgHTML($mail_generator->get_mail());
			// Generate list of super admin and admin emails
			$admin = $db->prepare("SELECT FirstName, LastName, Email, Notify FROM auth WHERE Notify = 1 AND Verified = 1 AND Activated = 1 AND Authority <= 2");
			$admin->execute();
			if($admin->rowCount() > 0) {
				while($admindata = $admin->fetch(PDO::FETCH_ASSOC)) {
					$mail->AddAddress($admindata['Email'], $admindata['FirstName']." ".$admindata['LastName']);
				}
			}

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

			// Automatically log user in
			// Query database for user details
			$q3 = $db->prepare("SELECT * FROM auth WHERE Email = :email AND VerificationKey = :verificationkey LIMIT 1");
			$q3->bindParam(':email', $email);
			$q3->bindParam(':verificationkey', $verificationkey);
			$q3->execute();
			$row = $q3->fetch(PDO::FETCH_ASSOC);

			session_regenerate_id();

			// Create JWT token and store it on the client-side
			$jwt = new \LotusBase\Users\AuthToken();
			$jwt->setUserData($row);
			$jwt->setValidDuration(MAX_USER_TOKEN_LIFESPAN);
			$jwt->setCookie();
			session_write_close();

			// Set redirection header
			$d = 5;
			header('refresh:'.$d.';url=./');

		} catch(PDOException $e) {
			$error[] = 'We have encountered an error with the database: '.$e->getMessage();
			$status = false;
		} catch(phpmailerexception $e) {
			$error[] = $e->getMessage();
			$status = false;
		} catch(Exception $e) {
			$error[] = $e->getMessage();
			$status = false;
		}

	} else {
		// If no request information is available, redirect user to homepage
		header("location: ".DOMAIN_NAME);
		exit();
	}

?>
<!doctype html>
<html lang="en">
<head>
	<title>User account verification&mdash;Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/users.min.css" type="text/css" media="screen" />
</head>
<body class="users verify">
	<?php

		$header_content = '';

		if($status) {
			$header_content .= '<div class="align-center"><h1><span class="icon-ok icon--no-spacing icon--big">Account verified</span></h1></div>
			<p>Thanks for verifying your account with us. You will be redirected to your profile page in a few seconds. If the redirection does not happen, you can <a href="'.WEB_ROOT.'/users/" title="Your profile page">follow this link</a>.</p>';
		} elseif(count($error)) {
			$header_content .= '<div class="align-center">
				<h1><span class="icon-attention icon--no-spacing icon--big"></span>Whoops!</h1>
				<p>We have encountered an issue when attempting to verify your account: '.$error[0].'</p>
			</div>';
		}

		// Generate header
		$header = new \LotusBase\PageHeader();
		$header->set_header_content($header_content);
		echo $header->get_header();
	?>

	<?php include(DOC_ROOT.'/footer.php'); ?>
</body>
</html>
