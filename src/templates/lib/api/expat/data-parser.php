<?php

// Initialize classes
$expat_query = new \LotusBase\ExpAt\Query();
$dataReturn = new \LotusBase\DataReturn();

// Coerce normalization type
$data_transform = false;
$data_transforms = array('normalize');
if(isset($_POST['data_transform'])) {
	if(empty($_POST['data_transform']) || !in_array($_POST['data_transform'], $data_transforms)) {
		$data_transform = false;
	} else {
		$data_transform = $_POST['data_transform'];
	}
}

// Perform query
$expat_query->set_column_type(array('Mean'));
$expat_query->set_clustering(true);
$expat_query->set_melting(true);
$expat_query->set_purpose('vis');
$expat_query->set_data_transform($data_transform);

// Retrieve data
$expat = $expat_query->execute();

// Return data
$dataReturn->set_data($expat);
$dataReturn->execute();

?>