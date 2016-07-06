<?php
// Clean up
$ver = strval($_GET['v']);

try {
	// Prepare query
	$q = $db->prepare("SELECT Chromosome, Position, Orientation, InsFlank
		FROM lore1ins
		WHERE
			IDKey = :id AND
			Version = :version
			");

	// Bind params and execute
	$q->bindParam(":id", $_GET['q']);
	$q->bindParam(":version", $ver);
	$q->execute();

	// Get results
	if($q->rowCount() == 1) {
		while($row = $q->fetch(PDO::FETCH_ASSOC)) {
			$chr = $row['Chromosome'];
			$pos = $row['Position'];
			$orn = $row['Orientation'];
			$ins = naseq($row['InsFlank']);
		}

		$dataReturn->set_data(array(
			'chromosome' => $chr,
			'position' => $pos,
			'orientation' => $orn,
			'insFlank' => $ins
		));
		$dataReturn->execute();		
	} else {
		$error->set_message('No rows have been returned.');
		$error->execute();
	}

} catch(PDOException $e) {
	$errorInfo = $db->errorInfo();
	$error->set_message('MySQL Error '.$errorInfo[1].': '.$errorInfo[2].'<br />'.$e->getMessage());
	$error->execute();
}
?>