<?php

// Get important files
require_once('../config.php');

// Get variables
if(isset($_GET['email'])) {
	$email = $_GET['email'];
} else {
	header("location: /");
	exit();
}

?>
<!doctype html>
<html lang="en">
<head>
	<title>Admin account verification&mdash;Lotus Base</title>
	<?php include('../head.php'); ?>
</head>
<body class="admin verify-success">
	<?php include('../header.php'); ?>
	<section class="wrapper">
	<?php 

	try {
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_ADMIN_USER, DB_ADMIN_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		$q1 = $db->prepare("SELECT FirstName, LastName, Username, Verified FROM auth WHERE Email = :email");
		$q1->bindParam(':email', $email);
		$q1->execute();

		if($q1->rowCount() != 1) {
			throw new PDOException('User does not exist. Are you sure you have registered?');
		}

		$result = $q1->fetch(PDO::FETCH_ASSOC);
		if($result['Verified'] == 1) {
			// If user has been previously verified
			?>
			<h2>Admin account verified</h2>
			<p>Dear <?php echo $result['FirstName']; ?>,</p>
			<p>Your account has already been verified, please <a href="login.php">proceed to the login page</a>.</p>
			<?php
		} else {
			// If user is verifying for the first time
			$q2 = $db->prepare("UPDATE auth SET Verified = 1 WHERE Email = :email");
			$q2->bindParam(':email', $email);
			$q2->execute();

			// Get super admin details
			$q3 = $db->prepare("SELECT auth.FirstName AS FirstName, auth.LastName AS LastName, auth.Email AS Email FROM auth LEFT JOIN adminprivileges AS adminrights ON auth.Authority = adminrights.Authority WHERE adminrights.Authority = 1");
			$q3->execute();

			// Notify admin to activate account
			$mail_admin = new PHPMailer(true);
			$body_admin	= '';
			$body_admin	.= '<body style="color: #555; font-family: Arial, Helvetica, sans-serif; margin: 12px; padding: 12px;">';
			$body_admin	.= '<table style="background-color: #eee; border: 0; line-height: 21px; font-size: 14px;" cellspacing="0" cellpadding="0" width="640">';
			$body_admin	.= '<tr>';
			$body_admin	.= '<td style="padding: 14px;" width="48"><img src="data/mail/branding.gif" alt="Lotus Base logo"></td>';
			$body_admin	.= '<td style="padding: 14px;"><h1 style="color: #444; font-size: 28px; margin: 8xp 0;">New Account Registration</h1></td>';
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
			$body_admin	.= '<strong>'.$result['FirstName'].' '.$result['LastName'].'</strong> has just registered for a new account with a username of <strong>'.$result['Username'].'</strong>.';
			$body_admin .= '<br /><br />';
			$body_admin	.= 'You can activate this account at:<br /><strong><a href="https://'.$_SERVER['HTTP_HOST'].'/admin/activate.php?email='.$email.'" style="color: #4a7298;">https://'.$_SERVER['HTTP_HOST'].'/admin/activate.php?email='.$email.'</a></strong>.';
			$body_admin .= '<br /><br />';
			$body_admin .= 'If you do not know this person who has registered this account, please ignore this email.';
			$body_admin	.= '</td>';
			$body_admin .= '</tr>';
			$body_admin	.= '</table>';
			$body_admin	.= '</body>';
			$mail_admin->IsSMTP(); // telling the class to use SMTP
			$mail_admin->Host       = SMTP_MAILSERVER; // SMTP server
			$mail_admin->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
			$mail_admin->CharSet    = "utf-8";
			$mail_admin->Subject    = "New Account Creation on Lotus Base Administration";
			$mail_admin->AltBody    = "To view the message, please use an HTML compatible email viewer."; // optional, comment out and test
			$mail_admin->MsgHTML($body_admin);

			// Add super admins
			while($admin = $q3->fetch(PDO::FETCH_ASSOC)) {
				$mail_admin->AddAddress($admin['Email'], $admin['FirstName'].' '.$admin['LastName']);
			}

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

			// Send mail
			$mail_admin->Send();

			// Success
			echo '<h2>Verification successful</h2><p class="user-message approved">Your account has been verified and awaiting activation from the super admin. You will receive another email when your account is activated.</p>';
		}

	} catch(PDOException $e) {
		echo '<h2>Admin account verification</h2><p class="user-message warning">We have encountered an error: '.$e->getMessage().'</p>';
	} catch(phpmailerexception $e) {
		echo '<h2>Admin account verification</h2><p class="user-message warning">We are unable to notify the superadmin of your verification, even though it is successful. Please write to Terry (terry@mbg.au.dk).</p><p>Error message: '.$e->errorMessage().'</p>';
	}

	?>
	</section>

	<?php include('../footer.php'); ?>
</body>
</html>
