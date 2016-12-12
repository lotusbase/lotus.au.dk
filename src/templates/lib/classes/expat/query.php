<?php

namespace LotusBase\ExpAt;
use \PDO;

/* ExpAt\Query */
class Query {

	private $error;

	private $_expat = array();
	private $expat = array();

	// Construct
	public function __construct() {
		// Initailize classes
		$this->error = new \LotusBase\ErrorCatcher();

		// Default internal values
		$_expat['clustering'] = false;
		$_expat['melting'] = true;
		$_expat['columnTypes'] = 'Mean';
		$_expat['purpose'] = 'vis';
		$_expat['dataTransform'] = false;
	}

	// PUBLIC
	// Set column type
	public function set_column_type($column_type) {
		$this->_expat['columnTypes'] = $column_type;
	}

	// PUBLIC
	// Set purpose
	public function set_purpose($purpose) {
		$this->_expat['purpose'] = $purpose;
	}

	// PUBLIC
	// Toggle melting
	public function set_melting($melting) {
		$this->_expat['melting'] = $melting;
	}

	// PUBLIC
	// Toggle clustering
	public function set_clustering($clustering) {
		$this->_expat['clustering'] = $clustering;
	}

	// PUBLIC
	// Toggle normalization
	public function set_data_transform($dataTransform) {
		$data_transform = false;
		$data_transforms = array('normalize', 'standardize');
		if(empty($dataTransform) || !in_array($dataTransform, $data_transforms)) {
			$data_transform = false;
		} else {
			$data_transform = $dataTransform;
		}
		$this->_expat['dataTransform'] = $data_transform;
	}

	// _private
	// Parse incoming ids
	private function parse_ids() {
		// Trim empty spaces in ids
		$ids = trim($_POST['ids']);
		$id_pattern = array(
			'/ *[\r\n]+/',		// Checks for one or more line breaks
			'/(\w)\s+(\w)/',	// Checks for words separated by one or more spaces
			'/,\s*/',			// Checks for words separated by comma, but with variable spaces
			'/,\s*,/'			// Checks for empty strings (i.e. no words between two commas)
			);
		$id_replace = array(
			',',
			'$1, $2',
			',',
			',',
			''
			);

		// Remove isoforms for dataset using gene IDs
		if($_POST['idtype'] == 'geneid') {
			$id_pattern[] = '/\.\d+/';
			$id_replace[] = '';
		}

		// Replace IDs by regex
		$ids = preg_replace($id_pattern, $id_replace, $ids);

		// Write to internal data
		$this->_expat['ids'] = array_values(array_unique(array_filter(explode(",", $ids))));
	}

	// _private
	// Parse incoming datasets
	private function parse_dataset() {
		$dataset = $_POST['dataset'];
		$this->expat['dataset'] = $dataset;

		// Set data transform
		$this->expat['dataTransform'] = $this->_expat['dataTransform'];

		// Perform sanity check on selected dataset
		if ($dataset == 'ljgea-geneid') {
			$this->_expat['query'] = array(
				'table' => 'expat_ljgea_geneid',
				'id' => $_POST['idtype'],
				'mappedid' => 'ProbeID'
			);
			$this->expat['experiment'] = 'ljgea';
			$this->expat['rowType'] = 'Gene ID';
			$this->expat['rowText'] = 'Gene(s)';
			$this->expat['mapped'] = array(
				'dataset' => 'ljgea-probeid',
				'rowType' => 'Probe ID',
				'text' => 'Probe(s)'
			);
		} else if ($dataset == 'ljgea-probeid') {
			$this->_expat['query'] = array(
				'table' => 'expat_ljgea_probeid',
				'id' => $_POST['idtype'],
				'mappedid' => ' GeneID'
			);
			$this->expat['experiment'] = 'ljgea';
			$this->expat['rowType'] = 'Probe ID';
			$this->expat['rowText'] = 'Probe(s)';
			$this->expat['mapped'] = array(
				'dataset' => 'ljgea-geneid',
				'rowType' => 'Gene ID',
				'text' => 'Gene(s)'
			);
		} else if ($dataset == 'rnaseq-simonkelly-2015-bacteria') {
			$this->_expat['query'] = array(
				'table' => 'expat_RNAseq_SimonKelly_bacteria',
				'id' => $_POST['idtype']
			);
			$this->expat['mapped'] = false;
			$this->expat['experiment'] = 'rnaseq-simonkelly-2015';
			$this->expat['rowType'] = 'Transcript ID';
			$this->expat['rowText'] = 'Transcript(s)';
		} else if ($dataset == 'rnaseq-simonkelly-2015-purifiedcompounds') {
			$this->_expat['query'] = array(
				'table' => 'expat_RNAseq_SimonKelly_purifiedcompounds',
				'id' => $_POST['idtype']
			);
			$this->expat['mapped'] = false;
			$this->expat['experiment'] = 'rnaseq-simonkelly-2015';
			$this->expat['rowType'] = 'Transcript ID';
			$this->expat['rowText'] = 'Transcript(s)';
		} else if ($dataset == 'rnaseq-marcogiovanetti-2015-am') {
			$this->_expat['query'] = array(
				'table' => 'expat_RNAseq_MarcoGiovanetti_AMGSE',
				'id' => $_POST['idtype']
			);
			$this->expat['mapped'] = false;
			$this->expat['experiment'] = 'rnaseq-marcogiovanetti-2015';
			$this->expat['rowType'] = 'Probe ID';
			$this->expat['rowText'] = 'Probe(s)';
		} else if ($dataset == 'rnaseq-eiichimurakami-2016-01') {
			$this->_expat['query'] = array(
				'table' => 'expat_RNAseq_EiichiMurakami',
				'id' => $_POST['idtype']
			);
			$this->expat['mapped'] = false;
			$this->expat['experiment'] = 'rnaseq-eiichimurakami-2016-01';
			$this->expat['rowType'] = 'Transcript ID';
			$this->expat['rowText'] = 'Transcript(s)';
		} else {
			$this->error->set_status(404);
			$this->error->set_message('The dataset you have selected is not available. Please try again.');
			$this->error->execute();
		}

		// Define datasets
		$datasets = array(
			'ljgea-geneid' =>  array(
				'WT_control1',
				'WT_Drought1',
				'Ljgln2_2_Control1',
				'Ljgln2_2_Drought1',
				'root_4dpicontrol1B',
				'root_4dpimycorrhized1D',
				'root_28dpicontrol1A',
				'root_28dpimycorrhized1C',
				'WT_root_tip_3w_uninocul_1',
				'WT_root_3w_uninocul_1',
				'WT_root_3w_5mM_nitrate_1',
				'WT_root_6w_5mM_nitrate_1',
				'WT_shoot_3w_5mM_nitrate_1',
				'WT_shoot_3w_uninocul_1',
				'WT_shoot_3w_inocul3_1',
				'WT_leaf_6w_5mM_nitrate_1',
				'WT_stem_6w_5mM_nitrate_1',
				'WT_flower_13w_5mM_nitrate_1',
				'har1_root_3w_uninocul_2',
				'har1_root_3w_inocul3_2',
				'har1_shoot_3w_uninocul_1',
				'har1_shoot_3w_inocul3_1',
				'WT_root_3w_nodC_inocul1_1',
				'WT_root_3w_inocul1_1',
				'WT_root_3w_inocul3_1',
				'WT_nodule_3w_inocul14_1',
				'WT_nodule_3w_inocul21_1',
				'WT_root_nodule_3w_inocul7_1',
				'WT_root_nodule_3w_inocul21_1',
				'WT_rootSZ_3w_uninocul_1',
				'WT_rootSZ_3w_Nod_inocul1_1',
				'WT_rootSZ_3w_inocul1_1',
				'nfr5_rootSZ_3w_uninocul_1',
				'nfr5_rootSZ_3w_inocul1_1',
				'nfr1_rootSZ_3w_uninocul_1',
				'nfr1_rootSZ_3w_inocul1_1',
				'nup133_rootSZ_3w_uninocul_1',
				'nup133_rootSZ_3w_inocul1_1',
				'cyclops_root_3w_uninocul',
				'cyclops_root_nodule_3w_inocul21',
				'nin_rootSZ_3w_uninocul_1',
				'nin_rootSZ_3w_inocul1_1',
				'sen1_root_3w_uninocul_1',
				'sen1_nodule_3w_inocul21_1',
				'sst1_root_3w_uninocul_1',
				'sst1_nodule_3w_inocul21_1',
				'cyclops_root_3w_inocul',
				'Shoot_0mM_sodiumChloride_1',
				'Shoot_25mM_sodiumChloride_Initial_1',
				'Shoot_50mM_sodiumChloride_Initial_1',
				'Shoot_75mM_sodiumChloride_Initial_1',
				'Shoot_50mM_sodiumChloride_Gradual_1',
				'Shoot_100mM_sodiumChloride_Gradual_1',
				'Shoot_150mM_sodiumChloride_Gradual_1',
				'Lburttii_Ctrol_A',
				'Lburttii_Salt_A',
				'Lcorniculatus_Ctrol_A',
				'Lcorniculatus_Salt_A',
				'Lfilicaulis_Ctrol_A',
				'Lfilicaulis_Salt_A',
				'Lglaber_Ctrol_A',
				'Lglaber_Salt_A',
				'Ljaponicus_Gifu_Ctrol_A',
				'Ljaponicus_Gifu_Salt_A',
				'Ljaponicus_MG20_Ctrol_A',
				'Ljaponicus_MG20_Salt_A',
				'Luliginosus_Ctrol_A',
				'Luliginosus_Salt_A',
				'Fl_1',
				'Pod20_1',
				'Seed10d_1',
				'Seed12d_1',
				'Seed14d_1',
				'Seed16d_1',
				'Seed20d_1',
				'Leaf_1',
				'Pt_1',
				'Stem_1',
				'Root_1',
				'Root0h_1',
				'Nod21_1'
			),
			'ljgea-probeid' => array(
				'WT_control1',
				'WT_Drought1',
				'Ljgln2_2_Control1',
				'Ljgln2_2_Drought1',
				'root_4dpicontrol1B',
				'root_28dpicontrol1A',
				'root_4dpimycorrhized1D',
				'root_28dpimycorrhized1C',
				'WT_root_tip_3w_uninocul_1',
				'WT_root_3w_uninocul_1',
				'WT_root_3w_5mM_nitrate_1',
				'WT_root_6w_5mM_nitrate_1',
				'WT_shoot_3w_5mM_nitrate_1',
				'WT_shoot_3w_uninocul_1',
				'WT_shoot_3w_inocul3_1',
				'WT_leaf_6w_5mM_nitrate_1',
				'WT_stem_6w_5mM_nitrate_1',
				'har1_root_3w_uninocul_2',
				'har1_root_3w_inocul3_2',
				'har1_shoot_3w_inocul3_1',
				'WT_root_3w_nodC_inocul1_1',
				'WT_root_3w_inocul1_1',
				'WT_root_3w_inocul3_1',
				'WT_nodule_3w_inocul14_1',
				'WT_nodule_3w_inocul21_1',
				'WT_root_nodule_3w_inocul21_1',
				'WT_rootSZ_3w_inocul1_1',
				'WT_rootSZ_3w_Nod_inocul1_1',
				'nfr5_rootSZ_3w_uninocul_1',
				'nfr5_rootSZ_3w_inocul1_1',
				'nfr1_rootSZ_3w_uninocul_1',
				'nfr1_rootSZ_3w_inocul1_1',
				'nup133_rootSZ_3w_uninocul_1',
				'nup133_rootSZ_3w_inocul1_1',
				'nin_rootSZ_3w_uninocul_1',
				'nin_rootSZ_3w_inocul1_1',
				'sen1_root_3w_uninocul_1',
				'sen1_nodule_3w_inocul21_1',
				'sst1_root_3w_uninocul_1',
				'sst1_nodule_3w_inocul21_1',
				'Shoot_0mM_sodiumChloride_1',
				'Shoot_25mM_sodiumChloride_Initial_1',
				'Shoot_50mM_sodiumChloride_Initial_1',
				'Shoot_75mM_sodiumChloride_Initial_1',
				'Shoot_50mM_sodiumChloride_Gradual_1',
				'Shoot_100mM_sodiumChloride_Gradual_1',
				'Shoot_150mM_sodiumChloride_Gradual_1',
				'Lburttii_Ctrol_A',
				'Lburttii_Salt_A',
				'Lcorniculatus_Ctrol_A',
				'Lcorniculatus_Salt_A',
				'Lfilicaulis_Ctrol_A',
				'Lfilicaulis_Salt_A',
				'Lglaber_Ctrol_A',
				'Lglaber_Salt_A',
				'Ljaponicus_Gifu_Ctrol_A',
				'Ljaponicus_Gifu_Salt_A',
				'Ljaponicus_MG20_Ctrol_A',
				'Ljaponicus_MG20_Salt_A',
				'Luliginosus_Ctrol_A',
				'Luliginosus_Salt_A',
				'Fl_1',
				'Pod20_1',
				'Seed10d_1',
				'Seed12d_1',
				'Seed14d_1',
				'Seed16d_1',
				'Seed20d_1',
				'Leaf_1',
				'Pt_1',
				'Stem_1',
				'Root_1',
				'Root0h_1',
				'Nod21_1'
			)
		);

		if(is_allowed_access('/expat/')) {
			$private_datasets = array(
				'rnaseq-simonkelly-2015-bacteria' => array(
					'277_exoU_24',
					'277_exoU_72',
					'277_exoYF_24',
					'277_exoYF_72',
					'277_H2O_24',
					'277_nodC_24',
					'277_R7A_24',
					'277_R7A_72',
					'311_exoU_24',
					'311_exoU_72',
					'311_exoYF_24',
					'311_exoYF_72',
					'311_H2O_24',
					'311_nodC_24',
					'311_R7A_24',
					'311_R7A_72',
					'G_exoU_24',
					'G_exoU_72',
					'G_exoYF_24',
					'G_exoYF_72',
					'G_H2O_24',
					'G_nodC_24',
					'G_R7A_24',
					'G_R7A_72'
				),
				'rnaseq-simonkelly-2015-purifiedcompounds' => array(
					'G_H2O_24',
					'277_H2O_24',
					'311_H2O_24',
					'G_NF_24',
					'277_NF_24',
					'311_NF_24',
					'G_R7AEPS_24',
					'277_R7AEPS_24',
					'311_R7AEPS_24',
					'G_UEPS_24',
					'277_UEPS_24',
					'311_UEPS_24',
					'G_NF_R7AEPS_24',
					'277_NF_R7AEPS_24',
					'311_NF_R7AEPS_24',
					'G_NF_UEPS_24',
					'277_NF_UEPS_24',
					'311_NF_UEPS_24'
				),
				'rnaseq-marcogiovanetti-2015-am' => array(
					'Control_H2O',
					'Treatment_AMGSE_24h',
					'Treatment_AMGSE_48h'
				),
				'rnaseq-eiichimurakami-2016-01' => array(
					'G_H2O',
					'G_NF',
					'38534_H2O',
					'38534_NF',
					'4820_H2O',
					'4820_NF',
					'nfr1_H2O',
					'nfr1_NF'
					)
			);

			$datasets = array_merge($datasets, $private_datasets);
		}

		// Write to private variable
		$this->_expat['datasets'] = $datasets;
	}

	// _private
	// Build columns
	private function build_columns() {
		// Construct column list
		if(isset($_POST['conditions']) && !empty($_POST['conditions'])) {
			// If sort order is defined, use it
			$this->_expat['columns'] = explode(',', $_POST['conditions']);
		} else {
			// If sort order undefined, use default
			$this->_expat['columns'] = $this->_expat['datasets'][$this->expat['dataset']];
		}

	}

	// Perform query
	public function execute() {

		// Parse ids and dataset
		$this->parse_dataset();
		$this->parse_ids();

		// Construct list of columns
		$this->build_columns();

		// Check if probeid database is used
		// If it is true, intersect them with known array because some columns are missing
		if($this->expat['dataset'] == 'ljgea-probeid') {
			$this->expat['columnNotFound'] = array_values(array_intersect(array_diff($this->_expat['datasets']['ljgea-geneid'], $this->_expat['datasets']['ljgea-probeid']), $this->_expat['columns']));
			$this->_expat['columns'] = array_values(array_intersect($this->_expat['datasets']['ljgea-probeid'], $this->_expat['columns']));
		}

		// Create zip archive if download is enabled
		if($this->_expat['purpose'] == 'download') {
			// File location and name
			$file_path = sys_get_temp_dir();
			$zip = new \ZipArchive;
			$zipFile = "lotus_expresstionData_" . date("Y-m-d_H-i-s") . "_" . bin2hex(mcrypt_create_iv(5, MCRYPT_DEV_URANDOM)) . ".zip";

			// Create file
			touch($file_path . '/' . $zipFile);

			// Open zip archive for writing
			$zip->open($file_path . '/' . $zipFile, \ZipArchive::CREATE);
		}

		// Iterate through all column types
		$columnTypes = $this->_expat['columnTypes'];
		foreach($columnTypes as $columnType) {

			// New ExpAt
			$expat = array();

			// Construct the right column names
			$columns = array();
			foreach($this->_expat['columns'] as $value) {
				$columns[] = $columnType.'_'.$value;
			}

			// Shorthand
			$query = $this->_expat['query'];
			$ids = $this->_expat['ids'];

			// Perform database query
			try {

				// Database connection
				$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

				// Formulate query
				if($this->expat['experiment'] === 'ljgea') {
					// LjGEA dataset requires joining of two different tables, one by gene ID and the other by probe ID
					$sqlQuery = "SELECT
						t1.".$query['id']." AS RowID, GROUP_CONCAT(t2.".$query['mappedid'].") AS MappedToID, t3.".$query['mappedid']." AS MappedID, ".implode($columns,',')."
					FROM
						".$query['table']." AS t1
					LEFT JOIN expat_mapping AS t2 ON t1.".$query['id']." = t2.".$query['id']."
					LEFT JOIN expat_ljgea_geneprobebesthit AS t3 ON t1.".$query['id']." = t3.".$query['id']."
					WHERE
						t1.".$query['id']." IN (".str_repeat('?,', count($ids)-1).'?'.")
					GROUP BY t1.".$query['id']."
					ORDER BY FIELD(t1.".$query['id'].", '".implode($ids,"','")."')";

					// Skip first three columns
					$colSkip = 3;

					// Define output header (only for download)
					$outHeader = "\"".$query['id']."\",\"Mapped ".$query['mappedid']."\",\"Best ".$query['mappedid']." hit\",\"".implode($columns,'","')."\"\n";

					// Set query array of $ids
					$ids_query = $ids;

				} else if ($this->expat['experiment'] === 'rnaseq-simonkelly-2015') {
					// Simon RNAseq dataset does not require joining
					// Construct LIKE query
					$likeQuery = '';
					foreach($ids as $id) {
						$likeQuery .= "t1.".$query['id']." LIKE ? OR ";
					}

					$sqlQuery = "SELECT
						t1.".$query['id']." AS RowID, ".implode($columns,',')."
					FROM
						".$query['table']." AS t1
					WHERE ".substr($likeQuery, 0, -4)."
					GROUP BY t1.".$query['id']."
					ORDER BY FIELD(t1.".$query['id'].", '".implode($ids,"','")."')";

					// Allow for wildcard search
					$ids_query = array();
					foreach($ids as $value) {
						$ids_query[] = $value.'%';
					}

					// Skip only the first column
					$colSkip = 1;

					// Define output header (only for download)
					$outHeader = "\"".$query['id']."\",\"".implode($columns,'","')."\"\n";

				} else if($this->expat['experiment'] === 'rnaseq-marcogiovanetti-2015' || $this->expat['experiment'] === 'rnaseq-eiichimurakami-2016-01') {
					// Marco Giovanetti's dataset does not require joining
					// Construct LIKE query
					$likeQuery = '';
					foreach($ids as $id) {
						$likeQuery .= "t1.".$query['id']." LIKE ? OR ";
					}

					$sqlQuery = "SELECT
						t1.".$query['id']." AS RowID, ".implode($columns,',')."
					FROM
						".$query['table']." AS t1
					WHERE ".substr($likeQuery, 0, -4)."
					GROUP BY t1.".$query['id']."
					ORDER BY FIELD(t1.".$query['id'].", '".implode($ids,"','")."')";

					// Allow for wildcard search
					$ids_query = array();
					foreach($ids as $value) {
						$ids_query[] = $value.'%';
					}

					// Skip only the first column
					$colSkip = 1;

					// Define output header (only for download)
					$outHeader = "\"".$query['id']."\",\"".implode($columns,'","')."\"\n";

				} else {
					
					$this->error->set_status(404);
					$this->error->set_message('The dataset you have selected is not available. Please try again.');
					$this->error->execute();

				}

				// prepare query
				$q = $db->prepare($sqlQuery);

				// Execute query with array of values
				$q->execute($ids_query);

				// Get results
				if($q->rowCount() > 0) {

					//==========================//
					// Manipulate and melt data //
					//==========================//
					$isFirstRow = true;

					/// Fetch results
					if($this->_expat['melting']) {
						while($row = $q->fetch(PDO::FETCH_ASSOC)) {

							$rowID = $row['RowID'];
							$expat['row'][] = $rowID;

							// Compute row mean
							$row_mean = array_sum(array_slice($row, $colSkip)) / (count($row) - $colSkip);

							// Get raw values for each row
							$row_raw_values = array_slice($row, $colSkip);

							// Normalize row data
							// For calculation, we need the minimum and maximum values
							if($this->_expat['dataTransform'] === 'normalize') {

								// Transform raw values using log
								$row_values = array_map(function($v) { return $v; }, $row_raw_values);

								// Compute min and max values
								$row_max = max($row_values);
								$row_min = min($row_values);

								if($row_max === $row_min) {
									$this->_expat['dataTransform'] = false;
								}
							}
							// Standardize row data
							// For calculation, we need the mean and standard deviation for each row
							elseif($this->_expat['dataTransform'] === 'standardize') {

								// Standard deviation
								// From: http://stackoverflow.com/a/5434698/395910
								// Function to calculate standard deviation (uses sd_square)    
								function sd($array) {
									// square root of sum of squares devided by N-1
									return sqrt(array_sum(array_map(function($x, $mean) {
										// Function to calculate square of value - mean
										return pow($x - $mean,2);
									}, $array, array_fill(0,count($array), (array_sum($array) / count($array)) ) ) ) / (count($array)-1) );
								}

								// Calculate standard deviation
								$row_sd = sd($row_raw_values);

								// Calculate mean
								$row_mean = array_sum($row_raw_values) / count($row_raw_values);
							}
							
							$colCount = 0;
							foreach($row as $key => $value) {
								$colCount++;
								if($colCount <= $colSkip) {
									if($colCount == 2 && $this->expat['mapped']) {
										// Explode probe list (comma-separated), only allow unique entries and reset array keys
										$this->expat['mappedTo'][$rowID] = array_values(array_unique(explode(',', $row[$key])));
									} else if($colCount == 3 && $this->expat['mapped']) {
										$this->expat['mappedToUnique'][$rowID] = $row[$key];
									}
									continue;
								}
								$colName = preg_replace('/^(.*?)_(.*)$/', '$2', $key);
								if($isFirstRow) {
									$expat['condition'][] = $colName;
								}
								$expat['melted'][] = array(
									'rowID' => $rowID,
									'condition' => $colName,
									'value' => (
										$this->_expat['dataTransform'] === 'normalize' ?
											round(($value - $row_min)/($row_max - $row_min), 3) :
											(
												$this->_expat['dataTransform'] === 'standardize' ?
													round(($value - $row_mean) / $row_sd, 3) :
														$value
												)
											)
								);
								$conditionValuePoints[] = array($colName, $value);
							}

							// We have left the first row
							$isFirstRow = false;
						}
					}
					// Produce raw output
					else {
						// Reset output and print header
						$out = '';
						$out .= $outHeader;

						// Write successive rows
						while($row = $q->fetch(PDO::FETCH_ASSOC)) {
							$out .= "\"".implode($row,'","')."\"\n";
						}
						
						// Add to zip archive
						$zip->addFromString($this->expat['dataset']."_".$columnType.".csv", $out);
					}

					//======================================================//
					// Return query IDs that are not found in search result //
					//======================================================//
					if($this->_expat['purpose'] == 'vis') {
						if($query['id'] == 'GeneID') {
							// If gene ID is being queried, remove isoforms from query ids
							foreach($ids as $id) {
								$queryRowIDs[] = preg_replace('/^(.*)\.\d+$/', '$1', $id);
							}
							$this->expat['notFound'] = array_diff($expat['row'], $queryRowIDs);

						} elseif($query['id'] == 'TranscriptID') {
							// If transcript ID is being queried, check if query ids are gene or transcripts
							foreach($expat['row'] as $r) {
								$rowGenes[] = preg_replace('/^(.*)\.\d+$/', '$1', $r);
							}

							foreach($ids as $id) {
								if(strpos($id, '.') !== false && !in_array($id, $expat['row'])) {
									$this->expat['notFound'][] = $id;
								} elseif(strpos($id, '.') === false && !in_array($id, $rowGenes)) {
									$this->expat['notFound'][] = $id;
								}
							}
						}
					}
					

					//====================//
					// Perform clustering //
					//====================//
					if($this->_expat['clustering']) {
						if(count($expat['condition']) >= 2 && count($expat['row']) >= 2) {
							// Perform hierarchical clustering
							// Generate parameters
							$clustering_params = array(
								'melted' => json_encode($expat['melted']),
								'row' => $expat['row'],
								'condition' => $expat['condition'],
								'config' => array(
									'rowClusterCutoff' => 0.25,
									'colClusterCutoff' => 0.25,
									'linkageMethod' => 'complete',
									'linkageMetric' => 'euclidean',
									'fclusterCriterion' => 'distance',
									'dataTransform' => $this->_expat['dataTransform']
									)
								);

							// Generate temp file
							$temp_file = tempnam(sys_get_temp_dir(), "expat_");
							if($writing = fopen($temp_file, 'w')) {
								fwrite($writing, json_encode($clustering_params));
							}
							fclose($writing);
							$expat['tempfile'] = $temp_file;

							// Execute python script
							$clustering = exec(PYTHON_PATH.' '.DOC_ROOT.'/lib/expat/hierarchical-clustering.py '.$temp_file);
							
							// Retrieve clustering results
							if($clustering != null) {
								$expat['clustering'] = json_decode($clustering);
							} else {
								$expat['clustering'] = null;
							}

							// Remove temp file
							unlink($temp_file);

						} else {
							// Perform k-means clustering
							// Generate parameters
							$clustering_params = array(
								'melted' => json_encode($expat['melted']),
								'row' => $expat['row'],
								'condition' => $expat['condition'],
								'config' => array()
								);

							// Generate temp file
							$temp_file = tempnam(sys_get_temp_dir(), "expat_");
							if($writing = fopen($temp_file, 'w')) {
								fwrite($writing, json_encode($clustering_params));
							}
							fclose($writing);
							$expat['tempfile'] = $temp_file;

							// Execute python script
							$clustering = exec(PYTHON_PATH.' '.DOC_ROOT.'/lib/expat/kmeans-clustering.py '.$temp_file);

							// Retrieve clustering results
							if($clustering != null) {
								$expat['clustering'] = json_decode($clustering);
							} else {
								$expat['clustering'] = null;
							}

							// Remove temp file
							unlink($temp_file);
						}
					}

					// Insert returned data
					if($this->_expat['purpose'] == 'vis') {
						$this->expat[$columnType] = $expat;
					} elseif($this->_expat['purpose'] == 'download') {

					} else {
						throw new \Exception('No valid purpose is provided.');
					}

				} else {
					throw new \Exception('No entires found in the expression atlas. Your ID might not be found in the dataset, or that you have mistakenly used an incorrect ID (e.g. a probe ID against a gene ID dataset).');
				}

			} catch(PDOException $e) {
				$this->error->set_status(500);
				$this->error->set_message($sqlQuery);
				$this->error->execute();
			} catch(\Exception $e) {
				$this->error->set_status(404);
				$this->error->set_message($e->getMessage());
				$this->error->execute();
			}
		}

		// Output
		if($this->_expat['purpose'] == 'vis') {
			return $this->expat;
		} elseif($this->_expat['purpose'] == 'download') {
			$zip->close();
			header('Content-Type: application/zip');
			header('Content-disposition: attachment; filename='.$zipFile);
			header('Content-Length: ' . filesize($file_path . '/' . $zipFile));
			readfile($file_path . '/' . $zipFile);
			unlink($file_path . '/' . $zipFile);
		} else {
			throw new \Exception('No valid purpose is provided.');
		}
	}
}
?>