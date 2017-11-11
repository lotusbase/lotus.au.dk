<?php

	// Use JWT
	use \Firebase\JWT\JWT;
	
	// Decode JWT if present
	if(isset($_COOKIE['auth_token']) && !empty($_COOKIE['auth_token'])) {
		try {
			// Cast decoded JWT to array
			$jwt_decoded = json_decode(json_encode(JWT::decode($_COOKIE['auth_token'], JWT_USER_LOGIN_SECRET, array('HS256'))), true);

			// Check if token has expired
			if($jwt_decoded['exp'] < time()) {
				setcookie('auth_token', '', time()-60, '/');
				header("Refresh:0");
				exit();
			}

			// Check if component paths are identical to database.
			// If not, revoke token and reload page
			// Deny access if component paths in token and database differs
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$q = $db->prepare("SELECT
					GROUP_CONCAT(components.Path) as ComponentPath
				FROM auth
				LEFT JOIN auth_group AS authGroup ON
					auth.UserGroup = authGroup.UserGroup
				LEFT JOIN components ON
					authGroup.ComponentID = components.IDKey
				WHERE
					auth.Salt = ?
				");
			$q->execute(array($jwt_decoded['data']['Salt']));
			$userData = $q->fetch(PDO::FETCH_ASSOC);
			$userComps = explode(',', $userData['ComponentPath']);
			$tokenComps = $jwt_decoded['data']['ComponentPath'];

			$diffComps = array_diff($userComps, $tokenComps);
			if(count($diffComps)) {
				// If user group access has been changed, force delete JWT cookie
				setcookie('auth_token', '', time()-60, '/');
				header("Refresh:0");
				exit();
			}

		} catch(Firebase\JWT\SignatureInvalidException $e) {
			// If signature is invalid, force delete JWT cookie
			setcookie('auth_token', '', time()-60, '/');
			header("Refresh:0");
			exit();
		}
	}
?>