<?php

	// Use JWT
	use \Firebase\JWT\JWT;

	try {
		// Deny access if user is not logged in
		if(
			!isset($_COOKIE['auth_token']) ||
			empty($_COOKIE['auth_token'])
			) {
			$origin = urlencode($_SERVER["REQUEST_URI"]);
			header("location: login.php?redir=".$origin);
		} else {
			// Attempt to decrypt token
			$jwt_decoded = json_decode(json_encode(JWT::decode($_COOKIE['auth_token'], JWT_SECRET, array('HS256'))), true);
			$user = $jwt_decoded['data'];

			// Deny access to users that have insufficient privilege
			if($user['Authority'] > 3) {
				$_SESSION['user_privilege_error'] = 'You do not have sufficient privilege to access the administrative interface.';
				header('Location: ../users/profile');
			}
		}
	} catch(Firebase\JWT\SignatureInvalidException $e) {
		setcookie('auth_token', '', time()-60, '/');
		$_SESSION['user_login_error'] = array($e->getMessage().'. There is a possibility that your user token has been tempered with.');
		header("location: login.php");
	} catch(Exception $e) {
		setcookie('auth_token', '', time()-60, '/');
		$_SESSION['user_login_error'] = array($e->getMessage().'. We have encountered a server side error that prevents us from authenticating your login attempt.');
		header("location: login.php");
	}

?>