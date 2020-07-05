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

		// Store array of columns to display for each experiment
		$dataset_metadata = array(
			'ljgea' => array(
				'table' => 'expat_ljgea_columns',
				'columns' => array(
					'PlantSpecies',
					'PlantEcotype',
					'PlantGenotype',
					'Standard',
					'ExperimentalFactor',
					'Age',
					'Inoculation',
					'Inocula',
					'InoculaStrain',
					'CultureSystem',
					'Organ',
					'TissueType',
					'Comments'
					)
				),
			'rnaseq-kellys-2015' => array(
				'table' => 'expat_RNAseq_KellyS_columns',
				'columns' => array(
					'ExperimentalFactor',
					'Treatment',
					'PlantSpecies',
					'PlantEcotype',
					'PlantGenotype',
					'Age',
					'Inoculation',
					'Inocula'
					)
				),
			'rnaseq-giovanettim-2015' => array(
				'table' => 'expat_RNAseq_GiovanettiM_AMGSE_columns',
				'columns' => array(
					'ExperimentalFactor',
					'Treatment',
					'PlantSpecies',
					'PlantEcotype',
					'Inoculation',
					'Inocula'
					)
				),
			'rnaseq-murakamie-2016' => array(
				'table' => 'expat_RNAseq_MurakamiE_columns',
				'columns' => array(
					'ExperimentalFactor',
					'Treatment',
					'PlantSpecies',
					'PlantEcotype',
					'PlantGenotype',
					'Age',
					'Inoculation',
					'Inocula'
					)
				),
			'rnaseq-handay-2015' => array(
				'table' => 'expat_RNAseq_HandaY2015_columns',
				'columns' => array(
					'Treatment',
					'Inocula',
					'Strain',
					'InoculationPressure',
					'SoilNutrientStatus',
					'TimeUnit',
					'TimeDuration',
					'PlantSpecies',
					'PlantEcotype',
					'PlantGenotype',
					'GrowthMedium',
					'GrowthTemperature',
					'DayNightRegime'
				)
			),
			'rnaseq-sasakit-2014' => array(
				'table' => 'expat_RNAseq_SasakiT2014_columns',
				'columns' => array(
					'Treatment',
					'Inocula',
					'Strain',
					'TimeUnit',
					'TimeDuration',
					'PlantSpecies',
					'PlantEcotype',
					'PlantGenotype',
					'Tissue'
				)
			),
			'rnaseq-suzakit-2014' => array(
				'table' => 'expat_RNAseq_SuzakiT2014_columns',
				'columns' => array(
					'Treatment',
					'Inocula',
					'Strain',
					'TimeUnit',
					'TimeDuration',
					'PlantSpecies',
					'PlantEcotype',
					'PlantGenotype',
					'Tissue'
				)
			),
			'rnaseq-davidm-2017' => array(
				'table' => 'expat_RNAseq_DavidM2017_columns',
				'columns' => array(
					'Treatment',
					'Inocula',
					'Strain',
					'TimeUnit',
					'TimeDuration',
					'PlantSpecies',
					'PlantEcotype',
					'Tissue',
					'Age'
				)
			),
			'rnaseq-kellys-2017' => array(
				'table' => 'expat_RNAseq_KellyS2017_MicrobialSpectrum_columns',
				'columns' => array(
					'Treatment',
					'Inocula',
					'Strain',
					'TimeUnit',
					'TimeDuration',
					'PlantSpecies',
					'PlantEcotype',
					'Tissue',
					'Age'
				)
			),
			'reidd-2019' => array(
				'table' => 'expat_ReidD2019_BarleyNutrients_columns',
				'columns' => array(
					'Treatment',
					'Tissue',
					'Nitrate',
					'Phosphate',
					'PlantSpecies',
					'PlantEcotype',
					'Organ',
					'Age',
				)
			),
			'reidd-2020' => array(
				'table' => 'expat_ReidD2020_GifuAtlas_columns',
				'columns' => array(
					'Treatment',
					'Inocula',
					'Strain',
					'TimeUnit',
					'TimeDuration',
					'PlantSpecies',
					'PlantEcotype',
					'Tissue',
				)
			),
			'montielj-2020' => array(
				'table' => 'expat_MontielJ2020_IRBG74Infection_columns`',
				'columns' => array(
					'Treatment',
					'Inocula',
					'Strain',
					'TimeUnit',
					'TimeDuration',
					'PlantSpecies',
					'PlantEcotype',
					'Tissue',
					'Age',
				)
			)
		);

		// Check if experiment exists
		if (!isset($dataset_metadata[$experiment])) {
			throw new Exception('Experiment does not exist.', 404);
		}

		$sqlQuery = "SELECT
			`ConditionName`,`".implode('`,`', $dataset_metadata[$experiment]['columns'])."`,`PMID`
			FROM ".$dataset_metadata[$experiment]['table']."
			WHERE Dataset LIKE ?
			ORDER BY ID";

		// Prepare query to collect PMIDs
		$q1 = $db->prepare('SELECT PMID FROM '.$dataset_metadata[$experiment]['table'].' GROUP BY PMID');
		$q1->execute();

		if(!$q1->rowCount()) {
			throw new Exception('No rows returned.', 400);
		} else {
			$pmids = array();
			while($row = $q1->fetch(PDO::FETCH_ASSOC)) {
				$pmids[] = $row['PMID'];
			}

			$refHandler = new \LotusBase\Getter\PMID;
			$refHandler->set_pmid($pmids);
			$refs = $refHandler->get_data();
		}

		// Prepare actual query
		$q2 = $db->prepare($sqlQuery);

		// Execute query with array of values
		$q2->execute(array($dataset_wildcard));

		// Get results
		if($q2->rowCount() > 0) {
			while($row = $q2->fetch(PDO::FETCH_ASSOC)) {

				// Check if a reference map is available first
				if(empty($refs)) {
					$row['Reference'] = 'N.A.';
					$row['ReferenceURL'] = null;
				}

				// Construct reference
				else if(!empty($row['PMID'])) {
					$ref = $refs[$row['PMID']];

					// Reference link
					$_articleids = $ref['articleids'];
					$doi = false;
					foreach ($_articleids as $ai) {
						if($ai['idtype'] === 'doi') {
							$doi = $ai['value'];
						}
					}

					// Reference authors
					$_authors = $ref['authors'];
					if(count($_authors) !== 2) {
						$authors = $_authors[0]['name'].' et al.';
					} else {
						$authors = implode(' and ', array_map(function($a) {
							return $a['name'];
						}, $_authors));
					}

					// Publication year
					$year = DateTime::createFromFormat('Y/m/d G:i', $ref['sortpubdate'])->format('Y');

					// Write to reference
					if(!empty($row['PMID'])) {
						$row['Reference'] = $authors.', '.$year;
						$row['ReferenceTitle'] = $ref['title'];
						$row['ReferenceURL'] = 'https://doi.org/'.$doi;
					}
				}
				
				else {
					$row['Reference'] = 'Unpublished data';
					$row['ReferenceURL'] = null;
				}

				// Remove PMID
				unset($row['PMID']);

				// Append data
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

	} catch(Exception $e) {

		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'code' => $e->getCode(),
				'data' => $e->getMessage()
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