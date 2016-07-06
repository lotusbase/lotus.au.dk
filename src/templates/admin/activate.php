<?php

// Only accessible to logged in admin (not anyone can activate!)
require_once('auth.php');

// Get important files
require_once('../config.php');
include_once('../functions.php');
require_once('../vendor/phpmailer/PHPMailerAutoload.php');

// Get variables
if(isset($_GET['email'])) {
	$email = $_GET['email'];
} else {
	header("location: /");
	exit;
}

try {
	$q1 = $db->prepare("SELECT FirstName, LastName, Email, Username, Verified, Activated FROM auth WHERE Email = :email");
	$q1->bindParam(':email', $email);
	$q1->execute();

	if($q1->rowCount() === 1) {
		$data = $q1->fetch(PDO::FETCH_ASSOC);
		if($data['Verified'] == 0) {
			$_SESSION['activate'] = '<p class="user-message warning">User account is awaiting verification, and thus not allowed for activated until verification is successful.</p>';
			session_write_close();
			header("location: /admin/");
			exit();

		} elseif($data['Verified'] == 1 && $data['Activated'] == 0) {
			$q2 = $db->prepare("UPDATE auth SET Activated = 1 WHERE Email = :email AND Verified = 1");
			$q2->bindParam(':email', $email);
			$q2->execute();
			

			if($q2->rowCount() !== 1) {
				$_SESSION['activate'] = '<p class="user-message warning">User account is awaiting verification, and thus not allowed for activated until verification is successful.</p>';
				session_write_close();
				header("location: /admin/");
				exit();
			}

			// Send email to user
			$mail_admin = new PHPMailer(true);
			$body_admin	= '';
			$body_admin	.= '<body style="color: #555; font-family: Arial, Helvetica, sans-serif; margin: 12px; padding: 12px;">';
			$body_admin	.= '<table style="background-color: #eee; border: 0; line-height: 21px; font-size: 14px;" cellspacing="0" cellpadding="0" width="640">';
			$body_admin	.= '<tr>';
			$body_admin	.= '<td style="padding: 14px;" width="48"><a href="http://'.$_SERVER['HTTP_HOST'].'/" title="Lotus Base"><img src="data/mail/branding.gif" alt="Lotus Base logo"></a></td>';
			$body_admin	.= '<td style="padding: 14px;"><h1 style="color: #444; font-size: 28px; margin: 8xp 0;"><em>Lotus</em> Base Admin Account Verified</h1></td>';
			$body_admin	.= '</tr>';
			$body_admin	.= '<tr>';
			$body_admin	.= '<td colspan="2" style="background-color: #aaa;" height="1"></td>';
			$body_admin	.= '</tr>';
			$body_admin	.= '<tr>';
			$body_admin	.= '<td colspan="2" style="background-color: #fff;" height="1"></td>';
			$body_admin	.= '</tr>';
			$body_admin	.= '<tr>';
			$body_admin	.= '<td colspan="2" style="background-color: transparent;" height="10"></td>';
			$body_admin	.= '</tr>';
			$body_admin	.= '<tr>';
			$body_admin	.= '<td style="padding: 14px;" colspan="2">';
			$body_admin	.= '<strong>Dear '.$data['FirstName'].',</strong>';
			$body_admin .= '<br /><br />';
			$body_admin .= 'Your account has been approved and activated by the administration. You can log in with your username ('.$data['Username'].') and password now at:';
			$body_admin	.= '<br />';
			$body_admin	.= '<strong><a href="http://'.$_SERVER['HTTP_HOST'].'/admin/login.php" style="color: #4a7298;">http://'.$_SERVER['HTTP_HOST'].'/admin/login.php</a></strong>.';
			$body_admin	.= '</td>';
			$body_admin .= '</tr>';
			$body_admin	.= '<tr>';
			$body_admin	.= '<td style="padding: 14px;" colspan="2">';
			$body_admin	.= 'Should you require any assistance, or have any enquiries, kindly contact us through the <a href="http://'.$_SERVER['HTTP_HOST'].'/contact.php" style="color: #4a7298;">contact form</a> on our site. <strong>Do not reply to this email because mails to this account will not be directed to any staff.</strong>';
			$body_admin	.= '<br /><br /><br />';
			$body_admin	.= 'Yours sincerely,<br />LORE1 Project Team<br />Centre for Carbohydrate Recognition and Signalling<br />Aarhus University<br />Gustav Wieds Vej 10<br />DK-8000 Aarhus C';
			$body_admin	.= '</td>';
			$body_admin	.= '</tr>';
			$body_admin	.= '</table>';
			$body_admin	.= '</body>';
			$body_admin	.= '';
			$mail_admin->IsSMTP(); // telling the class to use SMTP
			$mail_admin->IsHTML(true);
			$mail_admin->Host       = SMTP_MAILSERVER; // SMTP server
			$mail_admin->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
			$mail_admin->CharSet    = "utf-8";
			$mail_admin->Encoding   = "base64";
			$mail_admin->Subject    = "Lotus Base Admin Account Activated";
			$mail_admin->AltBody    = "To view the message, please use an HTML compatible email viewer."; // optional, comment out and test
			$mail_admin->MsgHTML($body_admin);
			$mail_admin->AddAddress($data['Email'], $data['FirstName']." ".$data['LastName']);
			$mail_admin->AddAttachment("data/mail/branding.gif");      // attachment
			$mail_admin->smtpConnect(
				array(
					"ssl" => array(
						"verify_peer" => false,
						"verify_peer_name" => false,
						"allow_self_signed" => true
					)
				)
			);

			$mail_admin->Send();
			$_SESSION['activate'] = '<p class="user-message approved">User account successfully activated.</p>';
			session_write_close();
			header("location: /admin/");
			exit();

		} elseif($data['Verified'] == 1 && $data['Activated'] == 1) {

			$_SESSION['activate'] = '<p class="user-message note">User account has been activated previously.</p>';
			session_write_close();
			header("location: /admin/");
			exit();
		}
	}

} catch(PDOException $e) {
	$_SESSION['activate'] = '<p class="user-message note">We have encountered an error in user activation: '.$e->getMessage().'</p>';
	session_write_close();
	header("location: /admin/");
	exit();
} catch(phpmailerexception $e) {
	$_SESSION['activate'] = '<p class="user-message note">We have encountered an error in PHP mailer: '.$e->errorMessage().'</p>';
	session_write_close();
	header("location: /admin/");
	exit();
}

?>