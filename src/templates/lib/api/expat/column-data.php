<?php
try {
// Formulate query
$experiment = $_GET['experiment'];
$dataset = $_GET['dataset'];

// Trim experiment from dataset variable
$dataset_wildcard = '%'.str_replace($experiment.'-', '', $dataset).'%';

if($experiment == 'ljgea') {
	$sqlQuery = "SELECT
		ConditionName,
		PlantSpecies,
		PlantEcotype,
		PlantGenotype,
		Standard,
		ExperimentalFactor,
		Age,
		Inoculation,
		Inocula,
		InoculaStrain,
		CultureSystem,
		Organ,
		TissueType,
		Comments,
		Reference,
		ReferenceTitle,
		ReferenceURL
	FROM expat_ljgea_columns
	WHERE Dataset LIKE ?
	ORDER BY ID";
} else if ($experiment == 'rnaseq-simonkelly-2015') {
	$sqlQuery = "SELECT
		ConditionName,
		ExperimentalFactor,
		Treatment,
		PlantSpecies,
		PlantEcotype,
		PlantGenotype,
		Age,
		Inoculation,
		Inocula
	FROM expat_RNAseq_SimonKelly_columns
	WHERE Dataset LIKE ?
	ORDER BY ID";
} else if ($experiment == 'rnaseq-marcogiovanetti-2015') {
	$sqlQuery = "SELECT
		ConditionName,
		ExperimentalFactor,
		Treatment,
		PlantSpecies,
		PlantEcotype,
		Inoculation,
		Inocula,
		Reference,
		ReferenceTitle,
		ReferenceURL
	FROM expat_RNAseq_MarcoGiovanetti_AMGSE_columns
	WHERE Dataset LIKE ?
	ORDER BY ID";
}

// Prepare query
$q = $db->prepare($sqlQuery);

// Execute query with array of values
$q->execute(array($dataset_wildcard));

// Get results
if($q->rowCount() > 0) {
	while($row = $q->fetch(PDO::FETCH_ASSOC)) {
		$group[] = $row;
	}
	$dataReturn->set_data($group);
	$dataReturn->execute();
}

} catch(PDOException $e) {
	$errorInfo = $db->errorInfo();
	$error->set_message('MySQL Error '.$errorInfo[1].': '.$errorInfo[2].'<br />'.$e->getMessage());
	$error->execute();
}
?>