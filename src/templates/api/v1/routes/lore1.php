<?php
// All LORE1 lines
$api->get('/lore1', function($request, $response, $args) {
	
	try {
		$db = $this->get('db');

		$q = $db->prepare('SELECT DISTINCT PlantID FROM lore1seeds WHERE Ordering = 1 AND SeedStock = 1 ORDER BY PlantID');
		$q->execute();
		
		$results = array();
		while($row = $q->fetch(PDO::FETCH_ASSOC)) {
			$results[] = $row['PlantID'];
		}

		return $response
			->withStatus(200)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 200,
				'data' => $results
				)));

	} catch(PDOException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'data' => $e->getMessage(),
				'code' => $e->getCode(),
				'more_info' => DOMAIN_NAME . WEB_ROOT . '/docs/api/v1#pdo-exception'
				),JSON_UNESCAPED_SLASHES));
	}
});

// Get insertion data
$api->get('/lore1/{pids}', function ($request, $response, $args) {

	try {
		$db = $this->get('db');

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

		// Get all the pids
		$pids = array_filter(explode(',', preg_replace($lines_pattern, $lines_replace, $args['pids'])));

		// Validate plant IDs first
		$pid_invalid = array();
		foreach ($pids as $pid) {
			if(!preg_match('/^((DK\d+\-0)?3\d{7}|[apl]\d{4,})$/i', $pid)) {
				$pid_invalid[] = $pid;
			}
		}

		// Strip invalid plant IDs from original query
		$pid_valid = array_values(array_diff($pids, $pid_invalid));

		// Generate placeholders
		if($pid_valid) {
			$placeholders = str_repeat('?,', count($pid_valid)-1).'?';
		} else {
			return $response
				->withStatus(404)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 404,
					'message' => 'No valid plant ID has been found.',
					'data' => array(
						'pid_invalid' => $pid_invalid
						),
					'more_info' => DOMAIN_NAME . WEB_ROOT . '/docs/api/v1#not-found'
					)
				,JSON_UNESCAPED_SLASHES));
		}

		// Perform query
		$q = $db->prepare("SELECT
			lore.PlantID,
			lore.Batch,
			lore.ColCoord,
			lore.RowCoord,
			lore.Chromosome,
			lore.Position,
			lore.Orientation,
			lore.CoordList,
			lore.CoordCount,
			lore.TotalCoverage,
			lore.FwPrimer,
			lore.RevPrimer,
			lore.PCRInsPos,
			lore.PCRWT
		FROM lore1ins AS lore
		WHERE lore.PlantID IN ($placeholders) AND lore.Version = '3.0'");
		$q->execute($pid_valid);

		// Get results
		if($q->rowCount()) {
			while($r = $q->fetch(PDO::FETCH_ASSOC)) {
				// Convert certain fields to numeric
				$numeric = array('Position', 'TotalCoverage', 'PCRInsPos', 'PCRWT');
				foreach ($numeric as $key) {
					$r[$key] = (int)$r[$key];
				}

				// Store in array
				$results[] = $r;
			}
			return $response
				->withStatus(200)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 200,
					'data' => $results
					))
				);
		} else {
			return $response
				->withStatus(404)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 404,
					'message' => 'No valid plant ID has been found.',
					'more_info' => DOMAIN_NAME . WEB_ROOT . '/docs/api/v1#404-not-found'
					)
				,JSON_UNESCAPED_SLASHES));
		}

	} catch(PDOException $e) {
		throw new Exception($e->getMessage());
	}

});

// Verify LORE1 lines
$api->get('/lore1/{pids}/verify', function ($request, $response, $args) {

	try {
		$db = $this->get('db');

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

		// Get all the pids
		$pids = array_filter(explode(',', preg_replace($lines_pattern, $lines_replace, $args['pids'])));

		// Validate plant IDs first
		$pid_invalid = array();
		foreach ($pids as $pid) {
			if(!preg_match('/^3(\d){7}$/', $pid)) {
				$pid_invalid[] = $pid;
			}
		}

		// Strip invalid plant IDs from original query
		$pid_valid = array_values(array_diff($pids, $pid_invalid));

		// Generate placeholders
		if($pid_valid) {
			$placeholders = str_repeat('?,', count($pid_valid)-1).'?';
		} else {
			return $response
				->withStatus(404)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 404,
					'message' => 'No valid plant ID has been found.',
					'data' => array(
						'pid_invalid' => $pid_invalid
						),
					'more_info' => DOMAIN_NAME . WEB_ROOT . '/docs/api/v1#not-found'
					)
				,JSON_UNESCAPED_SLASHES));
		}

		// Perform query
		$q = $db->prepare("SELECT DISTINCT PlantID FROM lore1seeds
			WHERE PlantID IN ($placeholders) AND SeedStock = 1 AND Ordering = 1
			ORDER BY PlantID
		");
		$q->execute($pid_valid);

		// Get results
		if($q->rowCount()) {
			$results_array = array();
			while($row = $q->fetch(PDO::FETCH_ASSOC)) {
				$results_array[] = $row['PlantID'];
			}
			$diff = array_diff($pid_valid, $results_array);
			sort($diff);
			if(!count($diff) && !count($pid_invalid)) {
				return $response
					->withStatus(200)
					->withHeader('Content-Type', 'application/json')
					->write(json_encode(array(
						'status' => 200,
						'data' => array(
							'pid_found' => $results_array
							)
						))
					);
			} else {
				$data = array();

				if($diff) {
					$data['pid_notFound'] = $diff;
				}

				$pid_found = array_intersect($pid_valid, $results_array);
				if($pid_found) {
					$data['pid_found'] = $pid_found;
				}

				if($pid_invalid) {
					$data['pid_invalid'] = $pid_invalid;
				}

				return $response
					->withStatus(207)
					->withHeader('Content-Type', 'application/json')
					->write(json_encode(array(
						'status' => 207,
						'message' => 'One or more plant ID you have provided are not available for ordering.',
						'data' => $data,
						'more_info' => DOMAIN_NAME . WEB_ROOT . '/docs/api/v1#207-partial-success'
						)
					,JSON_UNESCAPED_SLASHES));
			}
		} else {
			$data = array('pid_notFound' => $pid_valid);
			if($pid_invalid) {
				$data['pid_invalid'] = $pid_invalid;
			}

			return $response
				->withStatus(404)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 404,
					'message' => 'No valid plant ID has been found.',
					'data' => $data,
					'more_info' => DOMAIN_NAME . WEB_ROOT . '/docs/api/v1#404-not-found'
					)
				,JSON_UNESCAPED_SLASHES));
		}

	} catch(PDOException $e) {
		throw new Exception($e->getMessage());
	}
    
});

// LORE1 flanking sequence
$api->get('/lore1/flanking-sequence/v{version}/{id}[/{cutoff}]', function ($request, $response, $args) {

	try {
		$db = $this->get('db');

		// Sanity check for Lotus genome version
		$ver = new \LotusBase\LjGenomeVersion(array('version' => $args['version']));
		if(!$ver->check()) {
			return $response
				->withStatus(400)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 400,
					'message' => 'Invalid <em>Lotus japonicus</em> genome version selected.',
					'more_info' => DOMAIN_NAME . WEB_ROOT . '/docs/api/v1#invalid-lotus-genome-version'
				),JSON_UNESCAPED_SLASHES));
		}

		// Prepare query
		$q = $db->prepare("SELECT PlantID, Chromosome, Position, Orientation, InsFlank
			FROM lore1ins
			WHERE
				Salt = :salt AND
				Version = :version
				");

		// Bind params and execute
		$q->bindParam(":salt", hex2bin($args['id']));
		$q->bindParam(":version", $ver->check());
		$q->execute();

		// Get results
		if($q->rowCount()) {
			while($row = $q->fetch(PDO::FETCH_ASSOC)) {
				$pid = $row['PlantID'];
				$chr = $row['Chromosome'];
				$pos = $row['Position'];
				$orn = $row['Orientation'];
				$ins = trim(naseq(!empty($args['cutoff']) ? substr($row['InsFlank'], 1000-max($args['cutoff'],999), -(1000-max($args['cutoff'],999))): $row['InsFlank']));
			}

			return $response
				->withStatus(200)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 200,
					'data' => array(
						'plantID' => $pid,
						'chromosome' => $chr,
						'position' => $pos,
						'orientation' => $orn,
						'insFlank' => $ins,
						'cutoff' => !empty($args['cutoff']) ? !!$args['cutoff'] : false
						)
					))
				);

		} else {
			return $response
				->withStatus(404)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'message' => 'No flanking sequence found for the <em>LORE1</em> mutant line and <em>Lotus japonicus</em> genome combination.',
					'more_info' => DOMAIN_NAME . WEB_ROOT . '/docs/api/v1#404-not-found'
				),JSON_UNESCAPED_SLASHES));
		}

	} catch(PDOException $e) {
		throw new Exception($e->getMessage());
	}
});

// LORE1 orders by country
$api->get('/lore1/orders/all/by-country', function ($request, $response) {

	try {
		$db = $this->get('db');

		// Prepare query
		$q = $db->prepare("SELECT
				COUNT(t2.Salt) AS OrderCount,
				t1.CountryName AS CountryName,
				t1.Alpha3 AS CountryCode
			FROM countrycode AS t1
			LEFT JOIN orders_unique AS t2 ON
				t1.Alpha3 = t2.Country
			GROUP BY t1.Alpha3
			");
		$q->execute();

		// Get results
		if($q->rowCount()) {
			while($row = $q->fetch(PDO::FETCH_ASSOC)) {
				$countryData = array('countryCode' => $row['CountryCode'], 'countryName' => $row['CountryName'], 'orderCount' => intval($row['OrderCount']));
				$ordersByCountry[] = $countryData;
			}

			return $response
				->withStatus(200)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'data' => $ordersByCountry
					))
				);
		} else {
			return $response
				->withStatus(404)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'message' => 'No country data available for pre-existing LORE1 orders',
					'more_info' => DOMAIN_NAME . WEB_ROOT . '/docs/api/v1#404-not-found'
				),JSON_UNESCAPED_SLASHES));
		}

	} catch(PDOException $e) {
		throw new Exception($e->getMessage());
	}

});

?>