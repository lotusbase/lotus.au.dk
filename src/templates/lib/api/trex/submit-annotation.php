<?php
// Clean up
$gene = $_POST['g'];
$lj_gene_name = $_POST['a'];
$ver = intval($_POST['v']);
$email = $_POST['e'];
$literature = $_POST['l'];

try {

	// Check version against whitelist
	if(!in_array($ver, array(30), true)) {
		throw new Exception('Database name is invalid.');
	}

	// Additional checks
	if(!isset($lj_gene_name) || empty($lj_gene_name)) throw new Exception('Gene name suggestion is missing.');
	if(!isset($gene) || empty($gene)) throw new Exception('Gene name is missing.');
	if(!isset($email) || empty($email)) throw new Exception('User email is missing.');

	// Check if gene exists first
	$q1 = $db->prepare("SELECT * FROM annotations$ver WHERE Gene = ?");
	$q1->execute(array($gene));

	if($q1->rowCount() === 0) {
		throw new Exception('No gene found in database.');
	}
	$g = $q1->fetch(PDO::FETCH_ASSOC);
	if(!empty($g['LjAnnotation']) && $g['LjAnnotation'] === $lj_gene_name) {
		$error->set_data(array('errorType' => 'gene_exists', 'errorTarget' => 'annotation_gene'));
		throw new Exception('Annotation for this gene already exists: <code>'.$g['LjAnnotation'].'</code>.');
	}

	// Check if proposal is already under review
	$q2 = $db->prepare("SELECT * FROM annotations".$ver."_suggestions WHERE Gene = ? AND LjAnnotation = ?");
	$q2->execute(array($gene, $lj_gene_name));

	if ($q2->rowCount() > 0) {
		$error->set_data(array('errorType' => 'gene_under_review', 'errorTarget' => 'annotation_gene'));
		throw new Exception('This name has been proposed for the gene and is currently under review.');
	}

	// Prepare insertions
	$q3 = $db->prepare("INSERT INTO annotations".$ver."_suggestions (LjAnnotation, UserEmail, Gene, Literature) VALUES (?,?,?,?)");

	// Execute query with array of values
	$q3->execute(array($lj_gene_name, $email, $gene, $literature));

	// Get results
	if($q3->rowCount() > 0) {
		$dataReturn->execute();
	} else {
		$error->set_status(404);
		$error->set_message('Failed to submit gene annotation.');
		$error->execute();
	}

} catch(PDOException $e) {
	$errorInfo = $db->errorInfo();
	$error->set_status(500);
	$error->set_message('MySQL Error '.$errorInfo[1].': '.$errorInfo[2].'<br />'.$e->getMessage());
	$error->execute();
} catch(Exception $e) {
	$error->set_status(404);
	$error->set_message($e->getMessage());
	$error->execute();
}

?>