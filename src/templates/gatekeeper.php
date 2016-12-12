<?php
	require_once('config.php');

	// Definte custom exception class
	class GateKeeperException extends \Exception { }

	// Function: Serve file
	function serve_file($path) {
		$file_info = new finfo(FILEINFO_MIME_TYPE);
		$mime_type = $file_info->buffer(file_get_contents($path));

		// Define mime types
		$mime_types = array(
			'json' => 'text/json',
			'csv' => 'text/csv'
			);

		// Override Mimetype if is JSON
		$extension = pathinfo($path)['extension'];
		if(array_key_exists($extension, $mime_types)) {
			$mime_type = $mime_types[$extension];
		}

		header('Content-Type: '.$mime_type);
		echo file_get_contents($path);
	}

	// Check if file is defined in GET request
	if(empty($_GET) || empty($_GET['file'])) {
		header('Location: /');
	} else {
		$file_path = GATEKEEPER_PATH.'/'.$_GET['file'];

		// Get path parts
		$file_path_parts = pathinfo($file_path);

		// Determine if user has the privilege
		try {
			$user_auth = auth_verify($_COOKIE['auth_token']);

			// If user is logged in
			if(!$user_auth) {
				throw new GateKeeperException;
			}

			// If user does not belong to any group
			if(empty($user_auth['UserGroup']) || empty($user_auth['ComponentPath'])) {
				throw new GateKeeperException;
			}

			// If user has permission
			$cp_check = 0;
			foreach($user_auth['ComponentPath'] as $cp) {
				if(strpos($file_path, $cp) > -1) {
					$cp_check += 1;
					break;
				}
			}
			if($cp_check === 0) {
				throw new GateKeeperException;
			}

			// If all is good, serve restricted file
			$actual_path = $file_path_parts['dirname'].'/'.$file_path_parts['filename'].'.'.$user_auth['UserGroup'].'.'.$file_path_parts['extension'];
			serve_file($actual_path);

		} catch(GateKeeperException $e) {

			// User does not have access
			$actual_path = $file_path_parts['dirname'].'/'.$file_path_parts['filename'].'.public.'.$file_path_parts['extension'];
			serve_file($actual_path);

		} catch(\Exception $e) {

			// General exception
			header('Location: /');

		}
	}
?>