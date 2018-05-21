<?php
// Users API
$api->get('/users', function ($request, $response) {
	$response->write('Welcome to Lotus base users API v1');
	return $response;
});

// Add user to newsletter subscription
$api->post('/users/{salt}/subscription', function($request, $response, $args) {

	try {
		$p = $request->getParsedBody();

		// Enforce the rule that the user can only change his/her subscription
		$auth_token = json_decode(json_encode($request->getAttribute('user_auth_token')), true)['data'];
		if($args['salt'] !== $auth_token['Salt']) {
			throw new Exception('User identifier mismatch.', 401);
		}

		// Mailchimp action
		$MailChimp = new \DrewM\MailChimp\MailChimp(MAILCHIMP_API_KEY);
		$MailChimp_subscribe = $MailChimp->post("lists/".$p['list']['id']."/members", [
			'email_address' => $auth_token['Email'],
			'status'		=> 'subscribed',
			'merge_fields'	=> [
				'FNAME' => $auth_token['FirstName'],
				'LNAME' => $auth_token['LastName']
			]
		]);

		return $response
			->withStatus(200)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 200,
				'data' => $MailChimp_member
				)));

	} catch(Exception $e) {
		return $response
			->withStatus($e->getCode())
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => $e->getCode(),
				'message' => $e->getMessage()
				)));
	}
});

// Remove user to newsletter subscription
$api->delete('/users/{salt}/subscription', function($request, $response, $args) {

	try {
		$p = $request->getParsedBody();

		// Enforce the rule that the user can only change his/her subscription
		$auth_token = json_decode(json_encode($request->getAttribute('user_auth_token')), true)['data'];
		if($args['salt'] !== $auth_token['Salt']) {
			throw new Exception('User identifier mismatch.', 401);
		}

		// Mailchimp action
		$MailChimp = new \DrewM\MailChimp\MailChimp(MAILCHIMP_API_KEY);
		$MailChimp_member = $MailChimp->delete("lists/".$p['list']['id']."/members/".md5($auth_token['Email']));

		return $response
			->withStatus(200)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 200,
				'data' => $MailChimp_member,
				'message' => 'User successfully unsubscribed from the mailing list '.$p['list']['name']
				)));

	} catch(Exception $e) {
		return $response
			->withStatus($e->getCode())
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => $e->getCode(),
				'message' => $e->getMessage()
				)));
	}
});

// Remove user
$api->delete('/users/{salt}', function($request, $response, $args) {
	try {
		$p = $request->getParsedBody();
		$db = $this->get('admindb');

		// Enforce the rule that the user can only delete his/her account
		$auth_token = json_decode(json_encode($request->getAttribute('user_auth_token')), true)['data'];
		if($args['salt'] !== $auth_token['Salt']) {
			throw new Exception('User identifier mismatch.', 401);
		}

		// Remove user account from database
		$q1 = $db->prepare('DELETE FROM auth WHERE Salt = ?');
		$e1 = $q1->execute(array($auth_token['Salt']));

		if(!$e1) {
			throw new Exception('Unable to remove user from database.', 500);
		}

		// Clean user references from other tables
		// Remove user salt references from LORE1 orders
		$q2 = $db->prepare('UPDATE orders_unique SET UserSalt = NULL WHERE UserSalt = ?');
		$e2 = $q2->execute(array($auth_token['Salt']));
		if(!$e2) {
			throw new Exception('Unable to remove user references from LORE1 orders.', 500);
		}

		// Get CORNEA jobs associated with user salt
		$q3 = $db->prepare('SELECT * FROM correlationnetworkjob WHERE owner_salt = ?');
		$e3 = $q3->execute(array($auth_token['Salt']));
		if(!$e3) {
			throw new Exception('Unable to fetch CORNEA jobs associated with user account.', 500);
		}
		if($q3->rowCount()) {
			while($row = $q3->fetch(PDO::FETCH_ASSOC)) {
				$hash_id = $row['hash_id'];
				unlink(WEB_ROOT.'/data/cornea/jobs/'.$hash_id.'.json.gz');
			}
		}

		// Remove all CORNEA jobs associated with this user
		$q4 = $db->prepare('UPDATE correlationnetworkjob SET
			owner_salt = NULL,
			status = 5,
			owner = NULL
			WHERE owner_salt = ?');
		$e4 = $q4->execute(array($auth_token['Salt']));
		if(!$e4) {
			throw new Exception('Unable to remove user references from CORNEA networks.', 500);
		}

		// Unsubscribe user from MailChimp mailing list
		$MailChimp = new \DrewM\MailChimp\MailChimp(MAILCHIMP_API_KEY);
		$MailChimp_member = $MailChimp->delete("lists/c469e14ec3/members/".md5($auth_token['Email']));

		// Unset cookie used to store JWT
		setcookie('auth_token', '', time()-60, '/', '', true, false);

		return $response
			->withStatus(200)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 200,
				'title' => 'Account deleted',
				'message' => 'You have successfully deleted your account with <em>Lotus</em> Base. You will be redirected in 3 seconds.'
				)));

	} catch(PDOException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'message' => $e->getMessage(),
				'code' => $e->getCode()
				)));
	} catch(Exception $e) {
		return $response
			->withStatus($e->getCode())
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => $e->getCode(),
				'message' => $e->getMessage()
				)));
	}
});

// Update user oauth2 link
$api->put('/users/{salt}/oauth', function($request, $response, $args) {
	try {
		$p = $request->getParsedBody();
		$db = $this->get('db');

		// Check provider against whitelist
		if(!isset($p['provider'])) {
			throw new Exception('No OAuth provider is given.', 400);
		} else if(!in_array($p['provider'], array('Google', 'LinkedIn', 'GitHub'))) {
			throw new Exception('Invalid OAuth provider given.', 400);
		}

		// Enforce the rule that the user can only change his/her OAuth link
		$auth_token = json_decode(json_encode($request->getAttribute('user_auth_token')), true)['data'];
		if($args['salt'] !== $auth_token['Salt']) {
			throw new Exception('User identifier mismatch.', 401);
		}

		// Update profile
		$q1 = $db->prepare("UPDATE auth SET
			".$p['provider']."ID = NULL
			WHERE Salt = ?");
		$e1 = $q1->execute(array(
			$args['salt']
			));

		if($e1 && $q1->rowCount()) {
			return $response
				->withStatus(200)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 200,
					'message' => 'Successfully disconnected <em>Lotus</em> Base and '.$p['provider'].' accounts'
					)));
		} else {
			throw new Exception('Unable to establish database connection.', 500);
		}

	} catch(PDOException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'message' => $e->getMessage(),
				'code' => $e->getCode()
				)));
	} catch(Exception $e) {
		return $response
			->withStatus($e->getCode())
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => $e->getCode(),
				'message' => $e->getMessage()
				)));
	}
});

// Update user profile
$api->put('/users/{salt}/profile', function($request, $response, $args) {
	try {

		$p = $request->getParsedBody();
		$db = $this->get('db');

		// Check if information is provided
		if(!isset($p['firstname']) || empty($p['firstname'])) {
			throw new Exception('Please provide your first name.', 400);
		}
		if(!isset($p['lastname']) || empty($p['lastname'])) {
			throw new Exception('Please provide your last name.', 400);
		}
		if(!isset($p['username']) || empty($p['username'])) {
			throw new Exception('Please provide a username.', 400);
		}
		if(strlen($p['username']) < 2 || strlen($p['username']) > 255) {
			throw new Exception('Username must be between <strong>2</strong>&ndash;<strong>255</strong> characters long.', 400);
		}
		if(!isset($p['email']) || empty($p['email'])) {
			throw new Exception('Please provide an email address.', 400);
		}
		if(strlen($p['email']) > 255) {
			throw new Exception('Email must not be more than <strong>255</strong> characters long.', 400);
		}

		// Enforce the rule that the user can only change his/her own password
		$auth_token = json_decode(json_encode($request->getAttribute('user_auth_token')), true)['data'];
		if($args['salt'] !== $auth_token['Salt']) {
			throw new Exception('User identifier mismatch.', 401);
		}

		// Update profile
		$q2 = $db->prepare("UPDATE auth SET
			FirstName = ?,
			LastName = ?,
			Username = ?,
			Organization = ?,
			Address = ?,
			City = ?,
			State = ?,
			PostalCode = ?,
			Country = ?
			WHERE Salt = ?");
		$e2 = $q2->execute(array(
			$p['firstname'],
			$p['lastname'],
			$p['username'],
			isset($p['organization']) && !empty($p['organization'] && $p['organization'] !== 'none') ? $p['organization'] : null,
			isset($p['address']) && !empty($p['address']) ? $p['address'] : null,
			isset($p['city']) && !empty($p['city']) ? $p['city'] : null,
			isset($p['state']) && !empty($p['state']) ? $p['state'] : null,
			isset($p['postalcode']) && !empty($p['postalcode']) ? $p['postalcode'] : null,
			isset($p['country']) && !empty($p['country']) ? $p['country'] : null,
			$args['salt']
			));
		if($e2) {
			if($q2->rowCount() === 1 || $auth_token['Email'] !== $p['email']) {
				if($auth_token['Email'] !== $p['email']) {

					// Create new email confirmation key and timestamp
					$email_change_key = bin2hex(mcrypt_create_iv(8, MCRYPT_DEV_URANDOM));
					$q3 = $db->prepare('UPDATE auth SET EmailChangeKey = ?, EmailChangeTimestamp = NOW() WHERE Salt = ?');
					$q3->execute(array(
						$email_change_key,
						$args['salt']
						));

					// Send mail to user
					$mail = new \PHPMailer(true);

					// Construct mail
					$mail_generator = new \LotusBase\MailGenerator();
					$mail_generator->set_title('<em>Lotus</em> Base: Email change confirmation');
					$mail_generator->set_header_image('cid:mail_header_image');
					$mail_generator->set_content(array(
						'<h3 style="text-align: center; ">Confirm your new email</h3>
						<p>Hi '.$auth_token['FirstName'].',</p>
						<p>You are receiving this email because you have requested to update your email address associated with your <em>Lotus</em> Base user account. Please click on the following link within 24 hours to confirm this change:<br /><a href="'.DOMAIN_NAME.'/users/verify-email?email='.urlencode($p['email']).'&id='.$args['salt'].'&key='.$email_change_key.'">'.DOMAIN_NAME.'/users/verify-email?email='.urlencode($p['email']).'&id='.$args['salt'].'&key='.$email_change_key.'</a></p>
						<p>Ignore this email if you do not recall making such a request. Your account security has not been compromised.</p>
						'));

					$mail->IsSMTP();
					$mail->IsHTML(true);
					$mail->Host			= SMTP_MAILSERVER;
					$mail->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
					$mail->CharSet		= "utf-8";
					$mail->Encoding		= "base64";
					$mail->Subject		= "Lotus Base: Email change confirmation";
					$mail->AltBody		= "To view the message, please use an HTML compatible email viewer.";
					$mail->MsgHTML($mail_generator->get_mail());
					$mail->AddAddress($p['email'], $auth_token['FirstName'].' '.$auth_token['LastName']);

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

					// Return response
					return $response
						->withStatus(200)
						->withHeader('Content-Type', 'application/json')
						->write(json_encode(array(
							'status' => 200,
							'title' => 'Email change require confirmation',
							'message' => 'Profile update successful, but email change require verification. Please check your email at your new email address ('.$p['email'].') and follow the instructions in the email to confirm the change.',
							'data' => $p
							)));
				} else {
					return $response
						->withStatus(200)
						->withHeader('Content-Type', 'application/json')
						->write(json_encode(array(
							'status' => 200,
							'title' => 'Profile update successful',
							'message' => 'Profile update successful.',
							'data' => $p
							)));
				}
			} elseif($q2->rowCount() === 0) {
				return $response
					->withStatus(304)
					->withHeader('Content-Type', 'application/json');
			}
		} else {
			throw new Exception('Unable to update entry in database.', 401);
		}
	} catch(PDOException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'message' => $e->getMessage(),
				'code' => $e->getCode()
				)));
	} catch(phpmailerException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'message' => 'Unable to send confirmation email to new email address ('.$p['email'].'). The email in your profile is not updated.',
				'code' => $e->getCode()
				)));
	} catch(Exception $e) {
		return $response
			->withStatus($e->getCode())
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => $e->getCode(),
				'message' => $e->getMessage()
				)));
	}
});

// Update user password
$api->put('/users/{salt}/password', function($request, $response, $args) {
	try {

		$p = $request->getParsedBody();
		$db = $this->get('db');

		// Check if information is provided
		if(!isset($p['oldpass']) || empty($p['oldpass'])) {
			throw new Exception('Please authenticate with your current password.', 400);
		}
		if(!isset($p['newpass']) || !isset($p['newpass_rep']) || empty($p['newpass']) || empty($p['newpass_rep'])) {
			throw new Exception('Ensure that you have keyed in your new password twice.', 400);
		}
		if($p['newpass'] !== $p['newpass_rep']) {
			throw new Exception('New passwords do not match. Please check again.', 400);
		}

		// Enforce the rule that the user can only change his/her own password
		$auth_token = json_decode(json_encode($request->getAttribute('user_auth_token')), true)['data'];
		if($args['salt'] !== $auth_token['Salt']) {
			throw new Exception('User identifier mismatch.', 401);
		}

		// Verify that old password is correct
		$q1 = $db->prepare("SELECT * FROM auth WHERE Salt = ?");
		$e1 = $q1->execute(array($args['salt']));
		if($e1 && $q1->rowCount() === 1) {
			$row = $q1->fetch(PDO::FETCH_ASSOC);
			if(!password_verify($p['oldpass'], $row['Password'])) {
				throw new Exception('Unable to authenticate user. Have you entered the correct old password?', 401);
			}
		} else {
			throw new Exception('Unable to retrieve any rows from the database.', 400);
		}

		// Update password
		$q2 = $db->prepare("UPDATE auth SET Password = ? WHERE Salt = ?");
		$e2 = $q2->execute(array(
			password_hash($p['newpass'], PASSWORD_DEFAULT),
			$args['salt']
			));
		if($e2 && $q2->rowCount() === 1) {
			// Send email to notify user of password change
			$mail = new PHPMailer(true);

			// Construct mail
			$mail_generator = new \LotusBase\MailGenerator();
			$mail_generator->set_title('<em>Lotus</em> Base: Password changed on user account');
			$mail_generator->set_header_image('cid:mail_header_image');
			$mail_generator->set_content(array(
				'<h3 style="text-align: center; ">Password changed on <em>Lotus</em> Base user account</h3>
				<p>Hi '.$row['FirstName'].',</p>
				<p>You are receiving this email because we have registered a password change on your user account on <em>Lotus</em> Base.
				If you do not recall performing a password change, please <a href="'.WEB_ROOT.'/users/reset">reset your password immediately</a>.</p>
				'));

			$mail->IsSMTP();
			$mail->IsHTML(true);
			$mail->Host			= SMTP_MAILSERVER;
			$mail->AddReplyTo($row['Email'], $row['FirstName'].' '.$row['LastName']);
			$mail->SetFrom(NOREPLY_EMAIL, 'Lotus Base');
			$mail->CharSet		= "utf-8";
			$mail->Encoding		= "base64";
			$mail->Subject		= "Lotus Base: Password changed on user account";
			$mail->AltBody		= "To view the message, please use an HTML compatible email viewer.";
			$mail->MsgHTML($mail_generator->get_mail());
			$mail->AddAddress($row['Email'], $row['FirstName'].' '.$row['LastName']);

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

			// Return response
			return $response
				->withStatus(200)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 200,
					'message' => 'Password change for user '.$args['salt'].' successful.'
					)));
		} else {
			throw new Exception('Unable to update entry in database.', 401);
		}
	} catch(PDOException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'message' => $e->getMessage(),
				'code' => $e->getCode()
				)));
	} catch(phpmailerException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'message' => $e->getMessage(),
				'code' => $e->getCode()
				)));
	 } catch(Exception $e) {
		return $response
			->withStatus($e->getCode())
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => $e->getCode(),
				'message' => $e->getMessage()
				)));
	}
});

// Generate access token
$api->post('/users/access_token', function ($request, $response) {
	
	try {
		$p = $request->getParsedBody();
		$jwtData = json_decode(json_encode($request->getAttribute('user_auth_token')), true)['data'];

		// Create information needed to generate token
		$token		= bin2hex(mcrypt_create_iv(8, MCRYPT_DEV_URANDOM));
		$created	= time();
		$user_salt	= $jwtData['Salt'];

		// Generate access token and check if it's okay
		$access_token	= create_api_access_token($token, $created, $user_salt);

		// Insert token into database
		$db = $this->get('db');
		$q = $db->prepare('INSERT INTO apikeys (UserSalt, Token, Created, Comment) VALUES (?, ?, ?, ?)');
		$q->execute(array(
			$jwtData['Salt'],
			$token,
			date("Y-m-d H:i:s", $created),
			(isset($p['comment']) && !empty($p['comment']) ? $p['comment'] : NULL)
			));

		if($q->rowCount()) {
			return $response
				->withStatus(200)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 200,
					'data' => array(
						'access_token' => $access_token,
						'token' => $token,
						'created' => date("Y-m-d H:i:s", $created),
						'comment' => isset($p['comment']) ? $p['comment'] : false
						)
					)));
		} else {
			throw new Exception('Unable to insert newly created token into database.', 500);
		}
		

	} catch(PDOException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'message' => $e->getMessage(),
				'code' => $e->getCode()
				)));
	} catch(Exception $e) {
		return $response
			->withStatus($e->getCode())
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => $e->getCode(),
				'message' => $e->getMessage()
				)));
	}
});

// Delete access token
$api->delete('/users/access_token/{token}', function ($request, $response, $args) {
	try {
		$jwtData = json_decode(json_encode($request->getAttribute('user_auth_token')), true)['data'];

		// Check if token is specified
		if(!isset($args['token']) || empty($args['token'])) {
			throw new Exception('Token is not defined', 400);
		}

		$admindb = $this->get('admindb');
		$q = $admindb->prepare('DELETE FROM apikeys WHERE Token = ? AND UserSalt = ?');
		$r = $q->execute(array(
			$args['token'],
			$jwtData['Salt']
			));

		if($q->rowCount() && $r) {
			return $response
				->withStatus(200)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 200,
					'message' => 'Token deletion successful.',
					'data' => array(
						'token' => $args['token']
						)
					)));
		} else {
			throw new Exception('User and token combination does not exist. This could mean that the token has been deleted, or that the token and owner/user do not match.', 404);
		}

	} catch(PDOException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'message' => $e->getMessage(),
				'code' => $e->getCode()
				)));
	} catch(Exception $e) {
		return $response
			->withStatus($e->getCode())
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => $e->getCode(),
				'message' => $e->getMessage()
				)));
	}
});

?>