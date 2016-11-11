<?php

// Retrieve column data for ExpAt
$api->get('/expat/{experiment}/{dataset}', function($request, $response, $args) {

	try {

		$db = $this->get('db');

		// Formulate query
		$experiment = $args['experiment'];
		$dataset = $args['dataset'];

		// Trim experiment from dataset variable
		$dataset_wildcard = '%'.str_replace($experiment.'-', '', $dataset).'%';

		if($experiment === 'ljgea') {
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
		} else if ($experiment === 'rnaseq-simonkelly-2015') {
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
		} else if ($experiment === 'rnaseq-marcogiovanetti-2015') {
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
		} else if($experiment === 'rnaseq-eiichimurakami-2016-01') {
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
			FROM expat_RNAseq_EiichiMurakami_columns
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
			return $response
				->withStatus(200)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 200,
					'data' => $group
					)));
		}

	} catch(PDOException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'code' => $e->getCode(),
				'data' => $e->getMessage(),
				'more_info' => DOMAIN_NAME . '/' . WEB_ROOT . '/docs/errors/pdo-exception'
				),JSON_UNESCAPED_SLASHES));
	}
});

// Data parser
// Note: Technically we should use GET, but a lot of data is being transmitted so we have to use POST
$api->post('/expat', function($request, $response, $args) {

	// Initialize classes
	$expat_query = new \LotusBase\ExpAt\Query();

	// Get POST variables
	$p = $request->getParsedBody();

	// Coerce normalization type
	$data_transform = false;
	$data_transforms = array('normalize', 'standardize');
	if(isset($p['data_transform'])) {
		if(empty($p['data_transform']) || !in_array($p['data_transform'], $data_transforms)) {
			$data_transform = false;
		} else {
			$data_transform = $p['data_transform'];
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
	return $response
		->withStatus(200)
		->withHeader('Content-Type', 'application/json')
		->write(json_encode(array(
			'status' => 200,
			'data' => $expat
			)));
});

// Hierarchical clustering
$api->post('/expat/clustering', function($request, $response, $args) {

	try {
		// Get data
		$data = $request->getParsedBody();

		// Check data
		if(
			//array_key_exists('melted', $data) &&
			//array_key_exists('row', $data) &&
			//array_key_exists('condition', $data) &&
			//array_key_exists('config', $data)
			$data['melted']
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
			$clustering = exec(PYTHON_PATH.' '.DOC_ROOT.'/lib/expat/hierarchical-clustering.py '.$temp_file);
			
			// Return clustering results
			return $response
				->withStatus(200)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 200,
					'data' => json_decode($clustering)
					)));

			// Delete file
			unlink($temp_file);

		} else {
			return $response
				->withStatus(400)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 400,
					'message' => 'You have provided a malformed data scheme for clustering analysis.'
					)));
		}
	} catch(Exception $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'message' => $e->getMessage(),
				'code' => $e->getCode()
				)));
	}

});

?>