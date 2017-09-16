<?php

// Database connection
define('__ROOT__', dirname(dirname(dirname(__FILE__))));
require_once(__ROOT__.'/config.php');

// Get file name
$filename = $_GET['file'];
$fullPath = __ROOT__."/".$filename;

// Database connection
try {
	$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

	// Get list of files from database, and check again user query if they actually exist
	$q1 = $db->prepare("SELECT
			t1.FileKey AS FileKey,
			GROUP_CONCAT(t2.AuthGroup) AS AuthGroups
		FROM download AS t1
		LEFT JOIN download_auth AS t2 ON
			t1.FileKey = t2.FileKey
		WHERE CONCAT_WS('',t1.FilePath,t1.FileName) = :filename
		GROUP BY t1.FileKey
		ORDER BY t1.Category, t1.FileName");
	$q1->bindParam(':filename', $filename);
	$q1->execute();
	$f = $q1->fetch(PDO::FETCH_ASSOC);

	if ($q1->rowCount() === 1) {
		$file_exists = true;
	}
	
	// Only allow download when user is allow access to file
	// ...when AuthGroup is null, or when is not null, is found in the user
	$user_auth = auth_verify($_COOKIE['auth_token']);
	$user_groups = explode(',', $f['AuthGroups']);
	if ($f['AuthGroups'] !== null && !in_array($user_auth['UserGroup'], $user_groups)) {
		$_SESSION['download_error'] = 'You are not authenticated to download the file. Please <a href="'.WEB_ROOT.'/users/login?redir='.urlencode('/data/download').'">log in</a> to perform this action.';
		session_write_close();
		header('location: '.WEB_ROOT.'/data/download.php');
		exit();
	}

	unset($item);

	if(!$file_exists) {
		// If file does not exist
		$_SESSION['download_error'] = 'The requested file is no longer available.';
		session_write_close();
		header('location: '.WEB_ROOT.'/data/download.php');
		exit();
	} else {
		// If file exists
		if ($fd = fopen ($fullPath, "r")) {
			// Update download statistics
			$q2 = $db->prepare("UPDATE download SET Count = Count+1 WHERE CONCAT_WS('',FilePath,FileName) = :filename");
			$q2->bindParam(':filename', $filename);
			$q2->execute();

			// Output the file for download
			$fsize = filesize($fullPath);
			$path_parts = pathinfo($fullPath);
			
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");
			header("Content-Length: $fsize");
			header("Cache-Control: public"); //use this to open files directly
			while(!feof($fd)) {
				$buffer = fread($fd, 2048);
				echo $buffer;
			}
		} else {
			$_SESSION['download_error'] = 'The requested file is no longer available.';
			session_write_close();
			header('location: '.WEB_ROOT.'/data/download.php');
			exit();
		}

		fclose ($fd);
		exit();
	}
} catch(PDOException $e) {
	$_SESSION['download_error'] = 'There is a problem retrieving the download file: '.$e->getMessage();
	session_write_close();
	header('location: '.WEB_ROOT.'/data/download.php');
	exit();
}

?>