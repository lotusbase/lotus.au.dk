<?php

// Get important files
require_once('../config.php');

// Get variables
if(isset($_GET['email']) && isset($_GET['key'])) {
	$email = escapeHTML($_GET['email']);
	$salt = escapeHTML($_GET['key']);
} else {
	header("location: /");
	exit;
}

try {
	$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306", DB_USER, DB_PASS);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

	// Generate PlantID list
	$q1 = $db->prepare("SELECT PlantID FROM orders_lines WHERE Salt = :salt");
	$q1->bindParam(':salt', $salt);
	$q1->execute();
	$pids = array();
	if($q1->rowCount() > 0) {
		while($data = $q1->fetch(PDO::FETCH_ASSOC)) {
			$pids[] = $data['PlantID'];
		}
	} else {
		throw new Exception('Order not found');
	}

	// Get order info
	$q2 = $db->prepare("SELECT FirstName, LastName, Verified, Institution, Country FROM orders_unique WHERE Email = :email AND Salt = :salt");
	$q2->bindParam(':email', $email);
	$q2->bindParam(':salt', $salt);
	$q2->execute();

	$result = $q2->fetch(PDO::FETCH_ASSOC);

	$status = 0;

} catch(PDOException $e) {
	$status = 1;
	$message = $e->getMessage();
} catch(Exception $e) {
	$status = 2;
	$message = $e->getMessage();
}


?>
<!doctype html>
<html lang="en">
<head>
	<title>Order Verification &mdash; Lotus Base</title>
	<?php include(DOC_ROOT.'/head.php'); ?>
</head>
<body class="verify">
	<?php

		// Set relevant content
		$header_content = '';
		$body_content = '';
		if($status === 0) {
			// If order is present
			if($result['Verified'] == 1) {
				// If order has been verified
				$header_content .= '<h1 class="align-center"><span class="icon-ok icon--big icon--no-spacing"><strong>Hello, '.$result['FirstName'].'</strong></span></h1><span class="byline">You have previously verified your order.</span>';
				$body_content .= '<h3>Verification already performed</h3><p>Thank you for verifying your order with us previously. No further action from you is required. Your order is currently sitting in our processing queue, and you may track its status via this link: <br /><a href="'.DOMAIN_NAME.'/lore1/order-status?id='.$salt.'">'.DOMAIN_NAME.'/lore1/order-status?id='.$salt.'</a></p><p>You will receive an email from us after we have processed and shipped your order. The processing time depends on manpower availability and order volume on our end, but should not exceed 2&ndash;4 weeks.</p>';
				?>
				<?php
			} else {
				// If order has not been verified
				try {
					$q2 = $db->prepare("UPDATE orders_unique SET Verified = 1 WHERE Email = :email AND Salt = :salt");
					$q2->bindParam(':email', $email);
					$q2->bindParam(':salt', $salt);
					$q2->execute();

					$mail = new PHPMailer(true);

					// Construct mail
					$mail_generator = new \LotusBase\MailGenerator();
					$mail_generator->set_title('<em>Lotus</em> Base: New <em>LORE1</em> Order');
					$mail_generator->set_header_image('cid:mail_header_image');
					$mail_generator->set_content(array(
						'<strong>'.$result['FirstName'].' '.$result['LastName'].' from '.$result['Institution'].', '.$result['Country'].'</strong> has placed a new order for the following lines:
						<ul style="background-color: #eee; border: 1px solid #aaa; margin: 0; padding: 8px 8px 8px 32px;"><li>'.implode('</li><li>', $pids).'</li></ul>
						<p>You can process this order by visiting the <a href="https://'.$_SERVER['HTTP_HOST'].'/admin/orders.php?salt='.$salt.'&view=unprocessed">administration page</a>.</p>
						'));

					$mail->IsSMTP();
					$mail->IsHTML(true);
					$mail->Host			= SMTP_MAILSERVER;
					$mail->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
					$mail->CharSet		= "utf-8";
					$mail->Encoding		= "base64";
					$mail->Subject		= "Lotus Base: New LORE1 Order (".$salt.")";
					$mail->AltBody		= "To view the message, please use an HTML compatible email viewer.";
					$mail->MsgHTML($mail_generator->get_mail());
					// Generate list of admin emails
					$admin = $db->prepare("SELECT FirstName, LastName, Email, Notify FROM auth WHERE Notify = 1 AND Verified = 1 AND Activated = 1 AND Authority <= 3");
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

					// Set content
					$header_content .= '<h1 class="align-center"><span class="icon-ok icon--big icon--no-spacing"><strong>Thank you, '.$result['FirstName'].'.</strong></span></h1><span class="byline">Your <em>LORE1</em> order has been successfully verified.</span>';
					$body_content .= '<h3>Verification success</h3><p>Thank you for verifying your order with us. Your order is currently sitting in our processing queue, and you may track its status via this link: <br /><a href="'.DOMAIN_NAME.'/lore1/order-status?id='.$salt.'">'.DOMAIN_NAME.'/lore1/order-status?id='.$salt.'</a></p><p>You will be notified by email after we have processed and shipped your order. The processing time depends on manpower availability on our end, but should not exceed 2&ndash;4 weeks.</p>';

				} catch(PDOException $e) {
					// Set content
					$header_content .= '<h1 class="align-center"><span class="icon-attention icon--big icon--no-spacing">Whoops, we messed up!</span></h1><span class="byline">We have encountered an issue verifyingy your order.</span>';
					$body_content .= '<h3>Verification failure</h3><p>We have encountered an issue with verifyingy our order against our database&mdash;if you encounter this message again when you refresh the page, please <a href="'.WEB_ROOT.'/info/contact?key='.$salt.'" title="Contact Us">contact us with your order identifier</a>.</p>';

				} catch(phpmailerException $e) {
					// Set content
					$header_content .= '<h1 class="align-center"><span class="icon-ok icon--big icon--no-spacing"><strong>Thank you, '.$result['FirstName'].'.</strong></span></h1><span class="byline">Your <em>LORE1</em> order has been successfully verified.</span>';
					$body_content .= '<h3>Verification success</h3><p>Thank you for verifying your order with us. Your order is currently sitting in our processing queue, and you may track its status via this link: <br /><a href="'.DOMAIN_NAME.'/lore1/order-status?id='.$salt.'">'.DOMAIN_NAME.'/lore1/order-status?id='.$salt.'</a></p><p>You will be notified by email after we have processed and shipped your order. The processing time depends on manpower availability on our end, but should not exceed 2&ndash;4 weeks.</p>';
				}
			}
			$body_content .= '<h3>Enquiries</h3><p>If you would like to update your mailing address or contact us for issues relating to your order, feel free to drop us an email through the <a href="'.WEB_ROOT.'/info/contact?key='.$salt.'" title="Contact Us">contact page</a>.</p>';
		} else if($status === 1) {

		} else if($status === 2) {
			$header_content .= '<h1 class="align-center"><span class="icon-attention icon--big icon--no-spacing">Order not found</span></h1><span class="byline">Invalid order identifier provided.</span>';
			$header_content .= '<p>We are unable to verify your order because the identifier you have provided does not exist in our database. Please check that you have accessed the URL correctly. If the problem persists, please <a href="'.WEB_ROOT.'/meta/contact">contact us</a>.</p>';
		} else {

		}

	

		$header = new \LotusBase\PageHeader();
		$header->set_header_content($header_content);
		$header->set_header_background_image(WEB_ROOT.'/dist/images/hero/hero01.jpg');
		echo $header->get_header();
	?>

	<?php if(!empty($body_content)) { ?><section class="wrapper"><?php echo $body_content; ?></section><?php } ?>
	

	<?php include(DOC_ROOT.'/footer.php'); ?>
</body>
</html>
