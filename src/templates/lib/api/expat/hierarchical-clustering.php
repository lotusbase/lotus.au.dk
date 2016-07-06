<?php
// Convert to object
$data = $_POST['data'];

// Check data
if(
	array_key_exists('melted', $data) &&
	array_key_exists('row', $data) &&
	array_key_exists('condition', $data) &&
	array_key_exists('config', $data)
	) {
	// Perform hierarchical clustering
	// Generate temp file
	$temp_file = tempnam(sys_get_temp_dir(), "expat_");
	if($writing = fopen($temp_file, 'w')) {
		fwrite($writing, json_encode($data));
	}
	fclose($writing);
	$expat['tempfile'] = $temp_file;

	// Execute python script
	$clustering = exec(PYTHON_PATH.' lib/python/expat/hierarchical-clustering.py '.$temp_file);
	
	// Return clustering results
	$dataReturn->set_data(json_decode($clustering));
	$dataReturn->execute();

	// Delete file
	unlink($temp_file);
} else {
	
	$error->set_status(400);
	$error->set_message('You have provided a malformed data scheme for clustering analysis.');
	$error->execute();
}
?>