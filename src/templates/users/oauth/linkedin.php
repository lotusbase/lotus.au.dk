<?php

	// Load important files
	require_once('../../config.php');

	// LinkedIn
	const LINKEDIN_CLIENT_ID = '77zr2r19ed3dm0';
	const LINKEDIN_CLIENT_SECRET = 'LOwW4QrYoxiiSqQM';

	// Check if any information has been returned
	try {
		if($_GET) {
			if(isset($_GET['code']) && isset($_GET['state'])) {

				if($_GET['state'] !== $_SESSION['oauth2_state']) {
					$_SESSION['user_login_error'] = 'OAuth2 state mismatch, a possible CSRF attack detected.';
					session_write_close();
					header("location: ../login.php");
					exit();
				}

				// Exchange the OAuth 2.0 authorization code for user credentials.
				$post_url = 'https://www.linkedin.com/oauth/v2/accessToken';
				$post_fields = array(
					'grant_type' => 'authorization_code',
					'code' => $_GET['code'],
					'redirect_uri' => DOMAIN_NAME.'/users/oauth/linkedin',
					'client_id' => LINKEDIN_CLIENT_ID,
					'client_secret' => LINKEDIN_CLIENT_SECRET
					);
				$post_fields_string = '';
				foreach($post_fields as $key=>$value) {
					$post_fields_string .= $key.'='.$value.'&';
				}
				rtrim($post_fields_string, '&');

				// Construct POST request
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $post_url);
				curl_setopt($ch, CURLOPT_POST, count($post_fields));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields_string);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				// Execute POST request
				$linkedin_response = json_decode(curl_exec($ch), true);

				// Close connection
				curl_close($ch);


				// Construct GET request
				$get_url = 'https://api.linkedin.com/v1/people/~:(id,first-name,last-name,picture-url,public-profile-url,email-address)?format=json';
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $get_url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer '.$linkedin_response['access_token'],
					'Connection: Keep-Alive',
					'Host: api.linkedin.com'
					));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				// Execute GET request
				$linkedin_user = json_decode(curl_exec($ch), true);

				// Close connection
				curl_close($ch);
				
				// Integrate user
				$ui = new \LotusBase\Users\Integrate();
				$ui->setUserData(array(
					'FirstName' => $linkedin_user['firstName'],
					'LastName' => $linkedin_user['lastName'],
					'Email' => $linkedin_user['emailAddress'],
					'ID' => $linkedin_user['id'],
					'Avatar' => $linkedin_user['pictureUrl']
					));
				$ui->setProvider('LinkedIn');
				$ui->processUser();

			} else if(isset($_GET['error'])) {
				$_SESSION['user_login_error'] = 'We have encountered an error (code '.$_GET['error'].') with LinkedIn OAuth2 interface'.(isset($_GET['error_description']) && !empty($_GET['error_description']) ? ': '.$_GET['error_description'] : '.');
				session_write_close();
				header("location: ../login.php");
				exit();
			}

		} else {
			$_SESSION['user_login_error'] = 'OAuth2 authorization code not found in request. Please try another method to log in.';
			session_write_close();
			header("location: ../login.php");
			exit();
		}
	} catch(Exception $e) {
		$_SESSION['user_login_error'] = $e->getMessage();
		session_write_close();
		header("location: ../login.php");
		exit();
	}

?>