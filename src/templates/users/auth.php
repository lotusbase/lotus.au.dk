<?php

	// Use JWT
	use \Firebase\JWT\JWT;

	$userComps = array();

	// Custom error class
	class ComponentPathException extends \Exception {};

	try {
		// Deny access if user is not logged in
		if(
			!isset($_COOKIE['auth_token']) ||
			empty($_COOKIE['auth_token'])
			) {
			$origin = urlencode($_SERVER["REQUEST_URI"]);
			header("location: login.php?redir=".$origin);
			exit();
		} else {
			// Attempt to decrypt token
			$jwt_decoded = json_decode(json_encode(JWT::decode($_COOKIE['auth_token'], JWT_USER_LOGIN_SECRET, array('HS256'))), true);
			$user = $jwt_decoded['data'];

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
			$q->execute(array($user['Salt']));
			$userData = $q->fetch(PDO::FETCH_ASSOC);
			$userComps = explode(',', $userData['ComponentPath']);
			$tokenComps = $user['ComponentPath'];
			$diffComps = array_diff($userComps, $tokenComps);
			if(count($diffComps)) {
				throw new ComponentPathException('User group access privileges have been updated while you\'re logged in. Please reauthenticate.');
			}
		}
	} catch(Firebase\JWT\SignatureInvalidException $e) {
		setcookie('auth_token', '', time()-60, '/', '', true, false);
		$_SESSION['user_login_error'] = array('message' => $e->getMessage().'. There is a possibility that your user token has been tempered with.');
		header("location: login.php");
		exit();
	} catch(ComponentPathException $e) {
		setcookie('auth_token', '', time()-60, '/', '', true, false);
		header("location: login.php");
		exit();
	} catch(Exception $e) {
		setcookie('auth_token', '', time()-60, '/', '', true, false);
		$_SESSION['user_login_error'] = array('message' => $e->getMessage().'. We have encountered a server side error that prevents us from authenticating your login attempt.');
		header("location: login.php");
		exit();
	}

?>