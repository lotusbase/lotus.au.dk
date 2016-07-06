<?php
// Fetch variables
$ex = json_decode($_GET['q']);
$exArr = str_repeat('?,', count($ex)-1).'?';
$ver = strval($_GET['v']);
$strict = (isset($_GET['strict']) ? intval($_GET['strict']) : 1);

try {

	if($strict) {
		// Prepare query
		$q = $db->prepare("SELECT Gene, Annotation FROM annotations WHERE Version = ? AND Annotation IS NOT NULL AND Gene IN ($exArr)");

		// Execute query with array of values
		$q->execute(array_merge([$ver], $ex));
	} else {
		// Define statement
		$sql = "SELECT Gene, CASE WHEN Annotation IS NULL THEN NULL ELSE Annotation END AS Annotation FROM annotations WHERE Version = ? AND (";

		// Construct OR query
		foreach($ex as $key => $gene) {
			$sql .= 'Gene LIKE ? OR ';
			$ex[$key] = $gene.'%';
		}
		$sql = substr($sql, 0, -4);
		$sql .= ') GROUP BY Gene';
		
		// Prepare and execute
		$q = $db->prepare($sql);
		$q->execute(array_merge([$ver], $ex));
	}

	// Get results
	if($q->rowCount() > 0) {
		while($row = $q->fetch(PDO::FETCH_ASSOC)) {
			$out[] = array(
				'gene' => $row['Gene'],
				'annotation' => $row['Annotation']
				);
		}

		$dataReturn->set_data($out);
		$dataReturn->execute();
		
	} else {
		$error->set_status(404);
		$error->set_message('No gene annotation available.');
		$error->execute();
	}

} catch(PDOException $e) {
	$errorInfo = $db->errorInfo();
	$error->set_message('MySQL Error '.$errorInfo[1].': '.$errorInfo[2].'<br />'.$e->getMessage());
	$error->execute();
}

?>