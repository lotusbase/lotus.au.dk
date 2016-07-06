<?php

	// Load important files
	require_once('../../config.php');

	// Google+ login
	const GOOGLE_CLIENT_ID = '339332762863-r5hjkbsailkrdd0mii97230ifks373k8.apps.googleusercontent.com';
	const GOOGLE_CLIENT_SECRET = 'uEa9CBZMZ_Mqabb46EcyF1s4';
	const GOOGLE_APP_NAME = 'Lotus Base';

	// Check if any information has been returned
	if($_GET && $_GET['code']) {

		try {

			// Google Client
			$client = new \Google_Client();
			$client->setApplicationName(GOOGLE_APP_NAME);
			$client->setClientId(GOOGLE_CLIENT_ID);
			$client->setClientSecret(GOOGLE_CLIENT_SECRET);
			$client->setRedirectUri(DOMAIN_NAME.'/users/oauth/google');
			$client->addScope("email");
			$client->addScope("profile");

			// Exchange the OAuth 2.0 authorization code for user credentials.
			$client->authenticate($_GET['code']);
			$token = $client->getAccessToken();
			$client->setAccessToken($token);

			// Fetch user data
			$service = new \Google_Service_Oauth2($client);
			$google_user = $service->userinfo->get();

			// Integrate user
			$ui = new \LotusBase\Users\Integrate();
			$ui->setUserData(array(
				'FirstName' => $google_user['givenName'],
				'LastName' => $google_user['familyName'],
				'Email' => $google_user['email'],
				'ID' => $google_user['id'],
				'Avatar' => $google_user['picture']
				));
			$ui->setProvider('Google');
			$ui->processUser();

		} catch(Google_Auth_Exception $e) {
			print_r($e);
			exit();
			$_SESSION['user_login_error'] = 'We have encountered an issue with Google account authentication: '.$e->getMessage();
			$_SESSION['oauth_error'] = true;
			session_write_close();
			header("location: ../login.php");
			exit();
		} catch(PDOException $e) {
			$_SESSION['user_login_error'] = 'We have encountered a problem with our database: '. $e->getMessage();
			$_SESSION['oauth_error'] = true;
			session_write_close();
			header("location: ../login.php");
			exit();
		} catch(Exception $e) {
			$_SESSION['user_login_error'] = 'We have encountered a general error: '.$e->getMessage();
			$_SESSION['oauth_error'] = true;
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

?>