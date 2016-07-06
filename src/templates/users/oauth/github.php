<?php

	// Load important files
	require_once('../../config.php');

	// LinkedIn
	const GITHUB_CLIENT_ID = '651ee2e9ab91d2aa305f';
	const GITHUB_CLIENT_SECRET = '6f3aa3a1fdfc57046efad65ff92be32e5bc3a57c';

	// Check if any information has been returned
	try {
		if($_GET) {
			if(!empty($_GET['code']) && !empty($_GET['state']) && !empty($_SESSION['oauth2_state'])) {

				if($_GET['state'] !== $_SESSION['oauth2_state']) {
					$_SESSION['user_login_error'] = 'OAuth2 state mismatch, a possible CSRF attack detected.';
					session_write_close();
					header("location: ../login.php");
					exit();
				}

				// Exchange the OAuth 2.0 authorization code for user credentials.
				$post_url = 'https://github.com/login/oauth/access_token';
				$post_fields = array(
					'code' => $_GET['code'],
					'redirect_uri' => DOMAIN_NAME.'/users/oauth/linkedin',
					'client_id' => GITHUB_CLIENT_ID,
					'client_secret' => GITHUB_CLIENT_SECRET,
					'state' => $_SESSION['oauth2_state']
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
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Accept: application/json'
					));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				// Execute POST request
				$github_response = json_decode(curl_exec($ch), true);

				// Close connection
				curl_close($ch);

				
				// Check if access token is issued
				if(empty($github_response['access_token'])) {
					throw new Exception('No access token received from GitHub OAuth2.');
				}


				// Construct GET request
				$get_url = 'https://api.github.com/user';
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $get_url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Authorization: token '.$github_response['access_token'],
					'User-Agent: Lotus Base'
					));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				// Execute GET request
				$github_user = json_decode(curl_exec($ch), true);

				// Close connection
				curl_close($ch);



//				// Revoke user token when we have received user data
//				$delete_url = 'https://api.github.com/applications/'.GITHUB_CLIENT_ID.'/tokens/'.$github_response['access_token'];
//				$ch = curl_init();
//				curl_setopt($ch, CURLOPT_URL, $delete_url);
//				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
//				curl_setopt($ch, CURLOPT_USERPWD, GITHUB_CLIENT_ID . ":" . GITHUB_CLIENT_SECRET);
//				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//					'User-Agent: Lotus Base',
//					'Accept: application/vnd.github.damage-preview'
//					));
//				// Close connection
//				curl_close($ch);



				// Parse name
				$name = explode(' ', $github_user['name']);

				// Integrate user
				$ui = new \LotusBase\Users\Integrate();
				$ui->setUserData(array(
					'FirstName' => $name[0],
					'LastName' => count($name) > 1 ? $name[count($name) - 1] : null,
					'Email' => !empty($github_user['email']) ? $github_user['email'] : null,
					'ID' => $github_user['id'],
					'Avatar' => !empty($github_user['avatar_url']) ? $github_user['avatar_url'] : null
					));
				$ui->setProvider('GitHub');
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