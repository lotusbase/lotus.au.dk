<?php

// Use JWT
use \Firebase\JWT\JWT;

// Validation error
$error_messages = array();
$flag = false;

// Google Recaptcha
$recaptcha = new \ReCaptcha\ReCaptcha(GRECAPTCHA_API_KEY);

// Escape HTML
$fname 		= escapeHTML($_POST['fname']);
$lname 		= escapeHTML($_POST['lname']);
$email 		= escapeHTML($_POST['email']);
$emailver	= escapeHTML($_POST['emailver']);
$org		= escapeHTML(isset($_POST['organization']) ? $_POST['organization'] : '');
$topic		= escapeHTML($_POST['topic']);
$subject	= escapeHTML(isset($_POST['subject']) ? $_POST['subject'] : '');
$salt 		= escapeHTML($_POST['salt']);
$message 	= escapeHTML($_POST['message']);
$captcha	= escapeHTML($_POST['g-recaptcha-response']);

// Validate salt
$salt = preg_replace("/\s/", "", $salt);
if(!empty($salt)) {
	try {
		$q1 = $db->prepare("SELECT Salt FROM orders_unique WHERE Salt = :salt");
		$q1->bindParam(":salt", $salt);
		$q1->execute();

		if($q1->rowCount() > 0) {
			$row = $q1->fetch(PDO::FETCH_ASSOC);
		} else {
			$error_messages[] = 'The order ID you have provided is not found.';
			$flag = true;
		}

	} catch(PDOException $e) {
		$error_messages[] = 'Unable to execute query.';
		$flag = true;
	}
}

// Validate inputs
if($topic === '') {
	$error_messages[] = 'You have not selected a topic';
	$flag = true;
}
if($message === '') {
	$error_messages[] = 'You have not written a message';
	$flag = true;
}

// Attempt to decode JWT. If user is not logged in, perform additional checks
$user = auth_verify($_POST['user_auth_token']);
if(!$user) {
	if($fname === '') {
		$error_messages[] = 'First name is required';
		$flag = true;
	}
	if($lname === '') {
		$error_messages[] = 'Last name is required';
		$flag = true;
	}
	if($email === '') {
		$error_messages[] = 'Email is required';
		$flag = true;
	}
	if($email !== $emailver) {
		$error_messages[] = 'The two email addresses you have provided do not match';
		$flag = true;
	}
	if($captcha === '') {
		$error_messages[] = 'You have not completed the captcha';
		$flag = true;
	} else {
		$resp = $recaptcha->verify($captcha, get_ip());
		if(!$resp->isSuccess()) {
			$error->set_message('You have provided an incorrect verification token.');
			$error->execute();
		}
	}
} else {
	// Provide user details
	$fname	= $user['FirstName'];
	$lname	= $user['LastName'];
	$email	= $user['Email'];
	$org 	= $user['Organization'];
}

// Error catch
if($flag) {
	$error->set_message($error_messages);
	$error->execute();
} else {
	// Send verification email
	$mail = new PHPMailer(true);

	try {
		// Get super admin details
		$q2 = $db->prepare("SELECT auth.FirstName AS FirstName, auth.LastName AS LastName, auth.Email AS Email FROM auth LEFT JOIN adminprivileges AS adminrights ON auth.Authority = adminrights.Authority WHERE adminrights.Authority = 1");
		$q2->execute();

		// Construct mail
		$mail_generator = new \LotusBase\MailGenerator();
		$mail_generator->set_title('<em>Lotus</em> Base: Contact form submission');
		$mail_generator->set_header_image('cid:mail_header_image');
		$mail_generator->set_content(array(
			'<h3 style="text-align: center; ">'.($user ? 'Registered user' : 'User').' contact from <em>Lotus</em> Base</h3>
			<br>
			You have received a message from '.$fname.' '.$lname.'. '.($user ? 'The user is a registered member and logged in when this mail was sent.' : '').'
			<br><br>
			<strong>Topic: </strong> '.$topic.'
			<br>
			'.(!empty($org) ? '<strong>Organization: </strong>'.$org.'<br>' : '').'
			'.(!empty($subject) ? '<strong>Subject:</strong> '.$subject.'<br>' : '').'
			'.(!empty($salt) ? '<strong>Order ID:</strong> <a href="https://'.$_SERVER['HTTP_HOST'].'/admin/orders.php?salt='.$salt.'">'.$salt.'</a><br>' : '').'
			<strong>Message:</strong>
			<blockquote style="background-color: #ddd; padding: 16px; margin: 0; border: 1px solid #ccc;">'.$message.'</blockquote>
			'));

		$mail->IsSMTP();
		$mail->IsHTML(true);
		$mail->Host			= SMTP_MAILSERVER;
		$mail->AddReplyTo($email, $fname.' '.$lname);
		$mail->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
		$mail->CharSet		= "utf-8";
		$mail->Encoding		= "base64";
		$mail->Subject		= "Lotus Base user message on ".$topic.(!empty($subject) ? ': '.$subject : '');
		$mail->AltBody		= "To view the message, please use an HTML compatible email viewer.";
		$mail->MsgHTML($mail_generator->get_mail());
		
		// Add super admins
		while($admin = $q2->fetch(PDO::FETCH_ASSOC)) {
			$mail->AddAddress($admin['Email'], $admin['FirstName'].' '.$admin['LastName']);
		}

		$mail->AddEmbeddedImage(DOC_ROOT."/dist/images/branding/logo-256x256.png", mail_header_image);
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

		// Mail successfully sent
		$dataReturn->execute();

	} catch(phpmailerException $e) {
		// Mail has failed to send
		$error->set_message('We have encountered an error with sending your message. If the message persist, please contact Terry (<a href="mailto:terry@mbg.au.dk">terry@mbg.au.dk</a>) with the following message: <pre><code>'.$e->errorMessage().'</code></pre>');
		$error->execute();
	} catch(Exception $e) {
		// Mail has failed to send
		$error->set_message('We have encountered an error with sending your message. If the message persist, please contact Terry (<a href="mailto:terry@mbg.au.dk">terry@mbg.au.dk</a>) with the following message: <pre><code>'.$e->errorMessage().'</code></pre>');
		$error->execute();
	}
}
?>