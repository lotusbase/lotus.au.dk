<?php
// Clean up
$gene = $_GET['q'].'%';
$ver = $_GET['v'];

// Construct and run query
try {
	// Prepare query
	$q = $db->prepare("SELECT Annotation FROM annotations$ver WHERE Gene LIKE :gene AND Annotation IS NOT NULL");

	// Bind parameter
	$q->bindParam(":gene", $gene);

	// Execute query with array of values
	$q->execute();

	// Get results
	if($q->rowCount() > 0) {
		$row = $q->fetch(PDO::FETCH_ASSOC);
		$dataReturn->set_data(array('annotation' => $row['Annotation']));
		$dataReturn->execute();
	} else {
		$error->set_status(404);
		$error->set_message('No annotation found for the gene '.escapeHTML($_GET['q']).'.');
		$error->execute();
	}

} catch(PDOException $e) {
	$errorInfo = $db->errorInfo();
	$error->set_message('MySQL Error '.$errorInfo[1].': '.$errorInfo[2].'<br />'.$e->getMessage());
	$error->execute();
}
?>