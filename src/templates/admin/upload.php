<?php

	// Load site config
	require_once('../config.php');
	
	// Require authorization
	require_once('auth.php');

// Establish MySQL connection
try {
	$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
	$_SESSION['upload_error'] = 'Unable to establish a database connection.';
	session_write_close();
	header('location: downloads.php');
	exit();
}

// Flags
$error_flag = false;
$error_msg = array();

// Check input
if(!isset($_POST['filedesc'])) {
	$error_flag = true;
	$error_msg[] = 'No file description has been submitted.';
} else {
	$file_desc = $_POST['filedesc'];
}
if(empty($_POST['filedesc'])) {
	$error_flag = true;
	$error_msg[] = 'No file description has been entered.';
}

// Process file
if($_FILES['file']['error'] > 0) {
	$error_flag = true;
	$code = $_FILES['file']['error'];
	switch($code) {
		// Documentation for error codes: http://www.php.net/manual/en/features.file-upload.errors.php
		// 1: The uploaded file exceeds the upload_max_filesize directive in php.ini.
		case 1:
			$error_msg[] = 'The uploaded file size exceeds the <code>upload_max_filesize</code> directive in <code>php.ini</code>. Please check with system administrator.';
			break;

		// 2: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
		case 2:
			$error_msg[] = 'The uploaded file size exceeds the upload limit specified by MAX_FILE_SIZE directive.';
			break;

		// 3: The uploaded file was only partially uploaded.
		case 3:
			$error_msg[] = 'The uploaded file was only partially uploaded, and is therefore incomplete and potentially corrupted. Please try again.';
			break;

		// 4: No file was uploaded.
		case 4:
			$error_msg[] = 'No file was uploaded. Please select a file from your computer and try again.';
			break;

		// 6: Missing a temporary folder.
		case 6:
			$error_msg[] = 'Temporary folder is missing on the server. Please check with system administrator.';
			break;

		// 7: Failed to write file to disk. Introduced in PHP 5.1.0.
		case 7:
			$error_msg[] = 'Failed to write file to disk. Please check with system administrator.';
			break;

		// 8: A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0.
		case 8:
			$error_msg[] = 'A PHP extension has prevented a file upload. Please check with system administrator.';
			break;
	}
}

if($_FILES['file']['size'] > 52428800) {
	$error_flag = true;
	$error_msg[] = 'The uploaded file size exceeds the upload limit specified.';
}

// Process errors
if($error_flag) {
	$_SESSION['upload_error'] = implode(" ", $error_msg);
	session_write_close();
	header('location: downloads.php');
	exit();	
}

// Check for file integrity
if (is_uploaded_file($_FILES['file']['tmp_name'])) {

	// If everything is okay
	// Define upload directory
	$upload_dir = '../data/downloads/';
	$upload_file = $upload_dir.basename($_FILES['file']['name']);

	// Get file properties
	$file = pathinfo($_FILES['file']['name']);
	$file_dir = substr($upload_dir, 3);

	// Check if uploaded file exists
	$files = $db->prepare("SELECT FileName FROM download");
	$files->execute();

	$file_array = array();
	$file_exists = false;
	while($d = $files->fetch(PDO::FETCH_ASSOC)) {
		$file_array[] = $d['FileName'];
	}
	foreach($file_array as $item) {
		if(stripos($item, $file["basename"])) {
			$file_exists = true;
		}
	}

	if($file_exists || $_FILES['file']['name'] == 'index.php') {
		// Reject upload if file exists
		$_SESSION['upload_error'] = 'File already exists in the directory. Overwriting of files are not permitted.';
		session_write_close();
		header('location: downloads.php');
		exit();
	} else {
		// Proceed with upload and move uploaded file to the right directory
		if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_file)) {
			$update = $db->prepare("INSERT INTO download (FileName, FilePath, FileExt, FileDesc) VALUES (?,?,?,?)");
			$update->execute(array($file['basename'], $file_dir, $file['extension'], $file_desc));
			$_SESSION['upload_success'] = 'The file, <code>'.basename($_FILES['file']['name']).'</code> has been successfully uploaded to the server. It is now available in the <code>/data/downloads/</code> directory, and should appear in the downloads page.';
			session_write_close();
			header('location: downloads.php');
			exit();
		} else {
			$_SESSION['upload_error'] = 'Failed to move file to the correct directory. Upload attempt refused.';
			session_write_close();
			header('location: downloads.php');
			exit();	
		}
	}
} else {
	$_SESSION['upload_error'] = 'File integrity verification failed. Upload attempt refused.';
	session_write_close();
	header('location: downloads.php');
	exit();	
}

?>