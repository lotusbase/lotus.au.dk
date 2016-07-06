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
	$q1 = $db->prepare("SELECT * FROM download");
	$q1->execute();
	while($f = $q1->fetch(PDO::FETCH_ASSOC)) {
		$valid_files[] = $f['FileName'];
	}
	foreach($valid_files as $item) {
		if(stripos($fullPath, $item)) {
			$file_exists = true;
		}
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
} catch(PDOException $err) {
	$e = $db->errorInfo();
	$_SESSION['download_error'] = 'There is a problem retrieving the download file.';
	session_write_close();
	header('location: '.WEB_ROOT.'/data/download.php');
	exit();
}

?>