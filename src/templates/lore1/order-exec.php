<?php

	// Get important files
	require_once('../config.php');
	$recaptcha = new \ReCaptcha\ReCaptcha(GRECAPTCHA_API_KEY);

	// Validation error
	$order_error = array();
	$flag = false;
	$flag_lines = false;

	// Preserve input values in case user is sent back to the order form
	$user_input = array();

	try {

		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		// Sanitize POST values
		$fname 			= escapeHTML($_POST['fname']);
		$lname 			= escapeHTML($_POST['lname']);
		$email			= escapeHTML($_POST['email']);
		$institution 	= escapeHTML($_POST['shipping_institution']);
		$address 		= escapeHTML($_POST['shipping_address']);
		$city 			= escapeHTML($_POST['shipping_city']);
		$state 			= escapeHTML($_POST['shipping_state']);
		$postalcode 	= escapeHTML($_POST['shipping_postalcode']);
		$country 		= escapeHTML($_POST['shipping_country']);
		$lines 			= $_POST['lines']; 					// Don't sanitize lines YET (doing it later)
		$comments 		= escapeHTML($_POST['comments']);

		// Validate inputs
		if($fname == '') {
			$order_error[] = 'First name is required';
			$flag = true;
		}
		if($lname == '') {
			$order_error[] = 'Last name is required';
			$flag = true;
		}
		if($email == '') {
			$order_error[] = 'Email address is required';
			$flag = true;
		}
		if($institution == '' || $address == '' || $city == '' || $postalcode == '' || $country == '') {
			$order_error[] = 'Postal address is incomplete';
		}
		if($lines == '') {
			$order_error[] = 'No <em>LORE1</em> lines have been entered';
			$flag = true;
		}
		if(!isset($_POST['consent_disclaimer'])) {
			$order_error[] = 'You have not confirmed having applied for the necessary documentations for <em>LORE1</em> seeds import, when required.';
			$flag = true;
		}

		// CAPTCHA validation if user is not logged in
		$user = null;
		if(!empty($_POST['user_auth_token'])) {
			$user = auth_verify($_POST['user_auth_token']);
		}
		
		if(!$user) {
			if(!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
				$order_error[] = 'You have not completed the captcha';
				$flag = true;
			} else {
				$resp = $recaptcha->verify($_POST['g-recaptcha-response'], get_ip());
				if(!$resp->isSuccess()) {
					$order_error[] = 'You have provided an incorrect verification token.';
					$flag = true;
				}
			}
		}

		// Preserve user input
		$user_input = array(
			'FirstName' => $fname,
			'LastName' => $lname,
			'Email' => $email,
			'ShippingInstitution' => $institution,
			'ShippingAddress' => $address,
			'ShippingCity' => $city,
			'ShippingState' => $state,
			'ShippingPostalcode' => $postalcode,
			'ShippingCountry' => $country,
			'Lines' => $lines,
			'Comments' => $comments
			);

		// Throw exception if error flag is raised
		if($flag) {
			throw new Exception('');
		}

		// Format input for LORE1 lines such that each value is separated by ","
		// 1. Convert string $lines into array
		// 2. Filter array, remove empty strings
		// 3. Reset array keys
		$lines_array = array_values(array_filter(explode(",", $lines)));
		sort($lines_array);

		// Declare variables for error reporting
		$lines_error_title = '';
		$lines_error = array();

		// Convert array back into stirng for MySQL database search
		$q1 = $db->prepare("SELECT DISTINCT PlantID FROM lore1seeds WHERE PlantID IN (".str_repeat('?,', count($lines_array)-1).'?'.") AND SeedStock = 1 ORDER BY PlantID");
		$q1->execute($lines_array);

		$rows = $q1->rowCount();
		$real_array = array();
		if($rows > 0) {

			// If there are results

			// Fetch results
			while($row = $q1->fetch(PDO::FETCH_ASSOC)) {
				$real_array[] = $row['PlantID'];
			}
			$diff = array_diff($lines_array, $real_array);
			if(count($diff) > 0) {
				$flag = true;
				$flag_lines = true;
				$order_error[] = "Errors in your plant IDs - they are unavailable or are incorrectly formatted";
				$lines_error_title = count($diff)." of the ".pl(count($lines_array), "line", "lines")." that you have entered currently ".pl(count($lines_array), "is", "are")." unavailable. Please make sure you're using the correct format.";
				$lines_error = $diff;
				throw new Exception('');
			}

		} else {
			// If there are no results
			$flag = true;
			$flag_lines = true;
			$order_error[] = "Errors in your plant IDs - they are unavailable or are incorrectly formatted";
			$lines_error_title = "All lines that you have entered are unavailable. Please make sure you're using the correct format.";
			$lines_error = $lines_array;
		}

		// If everything is going well, proceed with database insertion
		//	Generate 32-character salt
		$salt = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));

		// Write to table that contains aggregated orders
		$insert_array = array($fname, $lname, $email, $institution, $address, $city, $state, $postalcode, $country, $comments, $salt, !empty($user['Salt'] ? $user['Salt'] : null));
		$q2 = $db->prepare("INSERT INTO orders_unique (FirstName,LastName,Email,Institution,Address,City,State,PostalCode,Country,Comments,Salt,UserSalt) VALUES (".str_repeat('?,', count($insert_array)-1).'?'.")");
		$q2->execute($insert_array);

		foreach($real_array as $value) {
			// Write to table that stores plant lines for each order
			$q3 = $db->prepare("INSERT INTO orders_lines (PlantID,Salt) VALUES(?,?)");
			$q3->execute(array($value, $salt));
		}

		// Send verification email
		$ordered_lines = explode(",", $lines);
		sort($ordered_lines);
		$mail = new PHPMailer(true);

		// Construct mail
		$mail_generator = new \LotusBase\MailGenerator();
		$mail_generator->set_title('<em>Lotus</em> Base: <em>LORE1</em> Order Verification');
		$mail_generator->set_header_image('cid:mail_header_image');
		$mail_generator->set_content(array(
			'<h3 style="text-align: center; "><em>LORE1</em> Order Verification</h3>
			<p>Thank you for ordering with us. Before we can proceed to process your order, we will require you to verify your order by visiting the link below. Your quick verification will help us to expedite the processing of your order.</p>
			<p><strong><a href="https://'.$_SERVER['HTTP_HOST'].'/lore1/verify?email='.urlencode($email).'&amp;key='.urlencode($salt).'">https://'.$_SERVER['HTTP_HOST'].'/lore1/verify.php?email='.urlencode($email).'&amp;key='.urlencode($salt).'</a></strong></p>
			<p>You will be notified by an email when we have processed and shipped your order to the mailing address provided. For your information, you have placed an order on the following lines:</p>
			<ul style="background-color: #eee; border: 1px solid #aaa; margin: 0; padding: 8px 8px 8px 32px;"><li>'.implode('</li><li>', $ordered_lines).'</li></ul>
			<p>Your order has been assigned an automatically generated identification chit (order key):<br /><strong>'.$salt.'</strong></p>
			<p>You may track your order status using the aforementioned order identifer, or the following link: <br /><a href="'.DOMAIN_NAME.'/lore1/order-status?id='.urlencode($salt).'"><strong>'.DOMAIN_NAME.'/lore1/order-status?id='.urlencode($salt).'</strong></a></p>
			<p>Should you require any assistance, or have any enquiries, kindly contact us through the <a href="https://'.$_SERVER['HTTP_HOST'].'/meta/contact.php?key='.urlencode($salt).'">contact form</a> on our site. <strong>Do not reply to this email because mails to this account (noreply@mb.au.dk) will not be directed to any staff.</strong></p>
			'));

		$mail->IsSMTP();
		$mail->IsHTML(true);
		$mail->Host			= SMTP_MAILSERVER;
		$mail->AddReplyTo($email, $fname.' '.$lname);
		$mail->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
		$mail->CharSet		= "utf-8";
		$mail->Encoding		= "base64";
		$mail->Subject		= "Lotus Base: LORE1 Order Verification";
		$mail->AltBody		= "To view the message, please use an HTML compatible email viewer.";
		$mail->MsgHTML($mail_generator->get_mail());
		$mail->AddAddress($email, $fname." ".$lname);
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

		// Inform database that verification email has indeed been sent
		$q4 = $db->prepare("UPDATE orders_unique SET VerificationEmail = 1 WHERE Salt = :salt");
		$q4->bindParam(":salt", $salt);
		$q4->execute();

		// Write session
		$_SESSION['order_success'] = array(
			'user_input' => $user_input
			);
		session_write_close();
		header('location: ./order-success.php');
		exit();

	} catch(PDOException $e) {

		$order_error[] = 'We have encountered a MySQL error: '.$e->getMessage();
		$_SESSION['order_error'] = array(
			'message' => 'Whoops! We have experienced problems processing your order. Please review the following:',
			'errors' => $order_error,
			'user_input' => $user_input
			);
		session_write_close();
		header('location: ./order.php');
		exit();

	} catch(phpmailerException $e) {

		$order_error[] = 'We have encountered an error with sending you an email: '.$e->getMessage();
		$$_SESSION['order_error'] = array(
			'message' => 'Whoops! We have experienced problems processing your order. Please review the following:',
			'errors' => $order_error,
			'user_input' => $user_input
			);
		session_write_close();
		header('location: ./order.php');
		exit();

	} catch(Exception $e) {

		$order_error[] = 'We have encountered an error with sending you an email: '.$e->getMessage();
		$_SESSION['order_error'] = array(
			'message' => $flag_lines ? $lines_error_title : 'Whoops! We have experienced problems processing your order. Please review the following:',
			'errors' => $flag_lines ? $lines_error : $order_error,
			'user_input' => $user_input
			);
		session_write_close();
		header('location: ./order.php');
		exit();
	}
?>