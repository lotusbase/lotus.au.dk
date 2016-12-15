<?php
// IP address
$api->get('/ip', function ($request, $response) {
	return $response
		->withStatus(200)
		->withHeader('Content-Type', 'application/json')
		->write(json_encode(array(
			'status' => 200,
			'data' => array(
				'ipAddress' => $_SERVER['REMOTE_ADDR'],
				'isIntranet' => is_intranet_client(),
				'ref' => $request->getHeaders()['Host'][0]
				)
			)));
});

// Contact form
$api->post('/contact', function($request, $response) {

	$db = $this->get('db');

	// From https://stackoverflow.com/questions/32668186/slim-3-how-to-get-all-get-put-post-variables/
	$allPostPutVars = $request->getParsedBody();
	foreach($allPostPutVars as $key => $param){
		$p[$key] = escapeHTML($param);
	}

	// Validation error
	$error_messages = array();

	// Google Recaptcha
	$recaptcha = new \ReCaptcha\ReCaptcha(GRECAPTCHA_API_KEY);

	// Validate salt
	$salt = preg_replace("/\s/", "", $p['salt']);
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
	if(!isset($p['topic']) || empty($p['topic'])) {
		$error_messages[] = 'You have not selected a topic';
		$flag = true;
	} else {
		$topic = $p['topic'];
	}
	if(!isset($p['message']) || empty($p['message'])) {
		$error_messages[] = 'You have not written a message';
		$flag = true;
	} else {
		$message = $p['message'];
	}
	$org = isset($p['organization']) ? $p['organization'] : '';
	$subject = isset($p['subject']) ? $p['subject'] : '';

	// Attempt to decode JWT. If user is not logged in, perform additional checks
	$user = null;
	if(!empty($p['user_auth_token'])) {
		$user = auth_verify($p['user_auth_token']);
	}
	
	if(!$user) {
		if(!isset($p['fname']) || empty($p['fname'])) {
			$error_messages[] = 'First name is required';
			$flag = true;
		}
		if(!isset($p['lname']) || empty($p['lname'])) {
			$error_messages[] = 'Last name is required';
			$flag = true;
		}
		if(!isset($p['email']) || empty($p['email'])) {
			$error_messages[] = 'Email is required';
			$flag = true;
		}
		if(!isset($p['emailver']) || empty($p['emailver'])) {
			$error_messages[] = 'Email confirmation is required';
			$flag = true;
		}
		if($p['email'] !== $p['emailver']) {
			$error_messages[] = 'The two email addresses you have provided do not match';
			$flag = true;
		}
		if(!isset($p['g-recaptcha-response']) || empty($p['g-recaptcha-response'])) {
			$error_messages[] = 'You have not completed the captcha';
			$flag = true;
		} else {
			$resp = $recaptcha->verify($p['g-recaptcha-response'], get_ip());
			if(!$resp->isSuccess()) {
				$error_messages[] = 'You have provided an incorrect verification token.';
			}
		}

		// Assign user details
		$fname	= $p['fname'];
		$lname	= $p['lname'];
		$email	= $p['email'];

	} else {
		// Provide user details
		$fname	= $user['FirstName'];
		$lname	= $user['LastName'];
		$email	= $user['Email'];
		$org 	= $user['Organization'];
	}

	// Error catch
	if(count($error_messages)) {
		return $response
			->withStatus(400)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 400,
				'messages' => $error_messages
			)));
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

			// Mail successfully sent
			return $response
				->withStatus(200)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 200,
					'message' => 'Mail successfully delivered.'
					)));

		} catch(phpmailerException $e) {

			// Mail has failed to send
			return $response
				->withStatus(500)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 500,
					'message' => 'We have encountered an error with sending your message. If the message persist, please contact Terry (<a href="mailto:terry@mbg.au.dk">terry@mbg.au.dk</a>) with the following message: <pre><code>'.$e->errorMessage().'</code></pre>',
					'code' => $e->getCode()
					)));
			
		} catch(PDOException $e) {

			// Mail likely has failed to send
			return $response
				->withStatus(500)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 500,
					'message' => 'We have encountered an error with sending your message. If the message persist, please contact Terry (<a href="mailto:terry@mbg.au.dk">terry@mbg.au.dk</a>) with the following message: <pre><code>'.$e->errorMessage().'</code></pre>',
					'code' => $e->getCode()
					)));
		} catch(Exception $e) {
			return $response
				->withStatus($e->getCode())
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => $e->getCode(),
					'message' => $e->getMessage(),
					)));
		}
	}
});

// Contact form
$api->post('/scaler', function($request, $response) {

	// From https://stackoverflow.com/questions/32668186/slim-3-how-to-get-all-get-put-post-variables/
	$p = $request->getParsedBody();

	try {

		if(empty($p)) {
			throw new Exception('No data is provided in the request body.', 400);
		}

		// Set values for scaling
		if(empty($p['values'])) {
			throw new Exception('No values have been provided for scaling.', 400);
		}
		$values = $p['values'];

		// Merge variables with default
		$_config = array(
			'min' => -5,
			'max' => 5,
			'scaleColumn' => 0,
			'fills' => array('#67001f','#b2182b','#d6604d','#f4a582','#92c5de','#4393c3','#2166ac','#053061')
			);
		$config = array_replace_recursive($_config, $p['config']);
		$p['config'] = $config;

		// Generate temp file
		$temp_file = tempnam(sys_get_temp_dir(), "scaler_");
		if($writing = fopen($temp_file, 'w')) {
			fwrite($writing, json_encode($p, JSON_NUMERIC_CHECK));
		}
		fclose($writing);

		$scaler = exec(NODE_PATH.' '.DOC_ROOT.'/lib/scaler.js '.$temp_file);

		// Delete file
		//unlink($temp_file);

		return $response
			->withStatus(200)
			->withHeader('Content-Type', 'application/json')
			->write($scaler);

	} catch(Exception $e) {
		return $response
				->withStatus($e->getCode())
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => $e->getCode(),
					'message' => $e->getMessage(),
					)));
	}

});

?>