<?php

	// Get important files
	require_once('../config.php');

	// Mailchimp API
	use \DrewM\MailChimp\MailChimp;

	// Use JWT
	use \Firebase\JWT\JWT;

	// Error flag
	$error = array();
	$status = true;

	// Get variables
	if(
		isset($_GET['email']) &&
		!empty($_GET['email']) &&

		isset($_GET['id']) &&
		!empty($_GET['id']) &&

		isset($_GET['key']) &&
		!empty($_GET['key'])
		) {

		$email	= escapeHTM($_GET['email']);
		$key	= escapeHTM($_GET['key']);
		$salt	= escapeHTM($_GET['id']);

		try {
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_ADMIN_USER, DB_ADMIN_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			// Get old email
			$q0 = $db->prepare('SELECT * FROM auth WHERE Salt = ?');
			$e0 = $q0->execute(array($salt));

			if(!$e0 || !$q0->rowCount()) {
				throw new Exception('Unable to retrieve user data from database.');
			}
			$user = $q0->fetch(PDO::FETCH_ASSOC);


			// Update with new email
			$q1 = $db->prepare("UPDATE auth
				SET
					Email = ?,
					EmailChangeKey = NULL,
					EmailChangeTimestamp = NULL
				WHERE
					Salt = ? AND
					EmailChangeKey = ? AND
					EmailChangeTimestamp > (NOW() - INTERVAL 24 HOUR)
				LIMIT 1");
			$e1 = $q1->execute(array(
				$email,
				$salt,
				$key
				));

			// Throw error for invalid email and/or verification key
			if(!$e1 || !$q1->rowCount()) {
				throw new Exception('Incorrect email, confirmation key and user identifier combination; or expired confirmation link. Please check that you have followed the correct link in the confirmation email we have sent you.');
			}


			// Subscribe new email to mailing list
			if($user['MailingList']) {
				$MailChimp = new MailChimp(MAILCHIMP_API_KEY);
				$MailChimp_subscribe = $MailChimp->post("lists/c469e14ec3/members", [
					'email_address' => $email,
					'status'		=> 'subscribed',
					'merge_fields'	=> [
						'FNAME' => $user['FirstName'],
						'LNAME' => $user['LastName']
					]
				]);
			}

			// Send mail to user to inform about email change
			$mail = new \PHPMailer(true);

			// Construct mail
			$mail_generator = new \LotusBase\MailGenerator();
			$mail_generator->set_title('<em>Lotus</em> Base: Email change confirmed');
			$mail_generator->set_header_image('cid:mail_header_image');
			$mail_generator->set_content(array(
				'<h3 style="text-align: center; ">User email updated</h3>
				<p>Hi '.$user['FirstName'].',</p>
				<p>We have successfully updated and confirmed your new email address. This email is sent as a receipt to your old email address to noify of this change.</p>
				<p>If you believe that your account has been compromised or that you have not authorized this change in email address, <a href="'.DOMAIN_NAME.'/users/reset">please reset your password immediately</a>.</p>
				'));

			$mail->IsSMTP();
			$mail->IsHTML(true);
			$mail->Host			= SMTP_MAILSERVER;
			$mail->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
			$mail->CharSet		= "utf-8";
			$mail->Encoding		= "base64";
			$mail->Subject		= "Lotus Base: Email change confirmed";
			$mail->AltBody		= "To view the message, please use an HTML compatible email viewer.";
			$mail->MsgHTML($mail_generator->get_mail());
			$mail->AddAddress($user['Email'], $user['FirstName']." ".$user['LastName']);
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
			$q3 = $db->prepare("SELECT * FROM auth WHERE Salt = ? LIMIT 1");
			$q3->execute(array($salt));
			$row = $q3->fetch(PDO::FETCH_ASSOC);

			session_regenerate_id();

			// Create JWT token and store it on the client-side
			$jwt = new \LotusBase\Users\AuthToken();
			$jwt->setUserData($row);
			$jwt->setValidDuration(MAX_USER_TOKEN_LIFESPAN);
			$jwt->setCookie();

			// Redirect user to dashboard
			$_SESSION['user_login_message'] = array(
				'classes' => array('approved'),
				'message' => '<span class="icon-ok"></span>Email on account successfully updated.'
				);
			session_write_close();
			header('location: index.php');
			exit();

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

	if(!$status) {

?>
<!doctype html>
<html lang="en">
<head>
	<title>User email change&mdash;Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
	<link rel="stylesheet" href="<?php echo WEB_ROOT; ?>/dist/css/users.min.css" type="text/css" media="screen" />
</head>
<body class="users verify">
	<?php

		$header_content = '';

		$header_content .= '<div class="align-center"><h1><span class="icon-attention icon--no-spacing icon--big">Unable to confirm email</span></h1></div>
		<p>'.$error[0].'</p>';

		// Generate header
		$header = new \LotusBase\Component\PageHeader();
		$header->set_header_content($header_content);
		echo $header->get_header();
	?>

	<?php include(DOC_ROOT.'/footer.php'); ?>
</body>
</html>
<?php } ?>