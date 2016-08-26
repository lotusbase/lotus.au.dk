<?php

// Get functions
require_once('../config.php');

// If no data is submitted
if(!isset($_POST) || empty($_POST)) {
	header('HTTP/1.1 401 Access Forbidden');
	header('Location: /error.php?status=401');
	exit();
}

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

?>