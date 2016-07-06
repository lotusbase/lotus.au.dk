<?php

	// Use JWT
	use \Firebase\JWT\JWT;
	
	// Decode JWT if present
	if(isset($_COOKIE['auth_token']) && !empty($_COOKIE['auth_token'])) {
		try {
			// Cast decoded JWT to array
			$jwt_decoded = json_decode(json_encode(JWT::decode($_COOKIE['auth_token'], JWT_SECRET, array('HS256'))), true);

			// Check if token has expired
			if($jwt_decoded['exp'] < time()) {
				setcookie('auth_token', '', time()-60, '/');
				header("Refresh:0");
			}
		} catch(Firebase\JWT\SignatureInvalidException $e) {
			// If signature is invalid, force delete JWT cookie
			setcookie('auth_token', '', time()-60, '/');
			header("Refresh:0");
		}
	}
?>