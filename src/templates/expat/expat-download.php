<?php

session_start();

// Get functions
require_once('../config.php');

// Try/catch
try {

	// Throw eception if no data is submitted
	if(!isset($_POST) || empty($_POST)) {
		throw new Exception('No data provided in POST request.');
	}

	// Verify CSRF token
	$csrf_protector->verify_token();
	
	// Set URL fragment
	$urlFragment = isset($_POST['url']) ? $_POST['url'] : '';

	// Initialize classes
	$expat_query = new \LotusBase\ExpAt\Query();

	// Perform query
	$expat_query->set_column_type(array('Mean','Std','SampleValues'));
	$expat_query->set_clustering(false);
	$expat_query->set_melting(false);
	$expat_query->set_purpose('download');
	$expat_query->set_data_transform(false);

	// Retrieve data
	$expat = $expat_query->execute();

	// Return data
	print_r($expat);

} catch(Exception $e) {
	$_SESSION['expat'] = array(
		'error' => true,
		'message' => $e->getMessage()
	);
	header('Location: /expat');
	session_write_close();
	exit();
}

?>