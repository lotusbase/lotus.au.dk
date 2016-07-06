<?php
// Get line, but clean later
$lines = $_GET['q'];

// Define replacement pattern
$lines_pattern = array(
	'/[\r\n]+/',		// Checks for one or more line breaks
	'/(\w)\s+(\w)/',	// Checks for words separated by one or more spaces
	'/,\s*/',			// Checks for words separated by comma, but with variable spaces
	'/,\s*,/'			// Checks for empty strings (i.e. no words between two commas)
	);
$lines_replace = array(
	',',
	'$1, $2',
	',',
	','
	);
$lines = preg_replace($lines_pattern, $lines_replace, $lines);
$query_array = array_filter(explode(",", $lines));

// Clean up
$linesArr = str_repeat('?,', count($query_array)-1).'?';

try {
	// Prepare query
	$q = $db->prepare("SELECT DISTINCT PlantID FROM lore1seeds
		WHERE PlantID IN ($linesArr) AND SeedStock = 1 AND Ordering = 1
		ORDER BY PlantID
	");

	// Execute query with array of values
	$q->execute($query_array);

	// Get results
	if($q->rowCount() > 0) {
		$results_array = array();
		while($row = $q->fetch(PDO::FETCH_ASSOC)) {
			$results_array[] = $row['PlantID'];
		}
		$diff = array_diff($query_array, $results_array);
		if(count($diff) > 0) {
			sort($diff);

			$error->set_status(404);
			$error->set_message('One or more plant ID you have provided are not available for ordering.');
			$error->set_data(array('pid' => $diff));
			$error->execute();
		} else {
			$dataReturn->set_data(array('pid' => $results_array));
			$dataReturn->execute();
		}
	} else {
		$error->set_status(404);
		$error->set_message('No valid plant ID has been found.');
		$error->set_data(array('pid' => $query_array));
		$error->execute();
	}

} catch(PDOException $e) {
	$errorInfo = $db->errorInfo();
	$error->set_message('MySQL Error '.$errorInfo[1].': '.$errorInfo[2].'<br />'.$e->getMessage());
	$error->execute();
}
?>