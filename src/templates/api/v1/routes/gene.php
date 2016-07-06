<?php

// Get annotation from genes
$api->get('/gene/annotation/v{version}/{id}[/{strict}]', function($request, $response, $args) {
	
	try {

		// Database
		$db = $this->get('db');

		// Fetch variables
		$ex		= explode(',', $args['id']);
		$exArr	= str_repeat('?,', count($ex)-1).'?';
		$strict	= isset($args['strict']) ? !!$args['strict'] : false;

		// Sanity check for Lotus genome version
		$v = new \LotusBase\LjGenomeVersion(array('version' => $args['version']));
		if(!$v->check()) {
			return $response
				->withStatus(400)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 400,
					'message' => 'Invalid <em>Lotus japonicus</em> genome version selected.',
					'more_info' => DOMAIN_NAME . WEB_ROOT . '/docs/errors/invalid-lotus-genome-version'
				),JSON_UNESCAPED_SLASHES));
		} else {
			$ver = $v->check();
		}

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

			return $response
				->withStatus(200)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 200,
					'data' => $out
					)));
			
		} else {
			return $response
				->withStatus(404)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 404,
					'message' => 'No gene annotation available.',
					'more_info' => DOMAIN_NAME . '/' . WEB_ROOT . '/docs/errors/not-found'
					),JSON_UNESCAPED_SLASHES));
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






// Submit new gene name suggestion
$api->post('/gene/name', function($request, $response, $args) {
	try {

		// From https://stackoverflow.com/questions/32668186/slim-3-how-to-get-all-get-put-post-variables/
		$allPostPutVars = $request->getParsedBody();
		foreach($allPostPutVars as $key => $param){
			$p[$key] = escapeHTML($param);
		}

		// Assign POST variables
		$gene			= $p['g'];
		$lj_gene_name	= $p['a'];
		$email			= $p['e'];
		$literature		= $p['l'];

		// Get version
		preg_match('/^Lj\dg(\d)v.*$/', $gene, $matches);
		$ver = intval($matches[0]);

		// Check version against whitelist
		if(!in_array($ver, array('3'), true)) {
			return $response
				->withStatus(400)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 400,
					'message' => 'Invalid <em>Lotus japonicus</em> genome version selected.',
					'more_info' => DOMAIN_NAME . WEB_ROOT . '/docs/errors/invalid-lotus-genome-version'
				),JSON_UNESCAPED_SLASHES));
		}

		// Additional checks
		if(!isset($lj_gene_name) || empty($lj_gene_name)) throw new Exception('Gene name suggestion is missing.');
		if(!isset($gene) || empty($gene)) throw new Exception('Gene name is missing.');
		if(!isset($email) || empty($email)) throw new Exception('User email is missing.');

		// Check if gene exists first
		$q1 = $db->prepare("SELECT * FROM annotations WHERE Gene = ? AND Version = ?");
		$q1->execute(array($gene, $ver));

		if($q1->rowCount() === 0) {
			return $response
				->withStatus(404)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'message' => 'Gene does not exist.',
					'more_info' => DOMAIN_NAME . '/' . WEB_ROOT . '/docs/errors/not-found'
				),JSON_UNESCAPED_SLASHES));
		}

		$g = $q1->fetch(PDO::FETCH_ASSOC);
		if(!empty($g['LjAnnotation']) && $g['LjAnnotation'] === $lj_gene_name) {
			return $response
				->withStatus(304)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'message' => 'Annotation for this gene already exists: <code>'.$g['LjAnnotation'].'</code>.',
					'data' => array('errorType' => 'gene_exists', 'errorTarget' => 'annotation_gene')
				)));
		}

		// Check if proposal is already under review
		$q2 = $db->prepare("SELECT * FROM annotations_suggestions WHERE Gene = ? AND LjAnnotation = ? AND Version = ?");
		$q2->execute(array($gene, $lj_gene_name, $ver));

		if ($q2->rowCount() > 0) {
			return $response
				->withStatus(304)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'message' => 'This name has been proposed for the gene and is currently under review.',
					'data' => array('errorType' => 'gene_under_review', 'errorTarget' => 'annotation_gene')
				)));
		}

		// Prepare insertions
		$q3 = $db->prepare("INSERT INTO annotations_suggestions (LjAnnotation, UserEmail, Gene, Literature, Version) VALUES (?,?,?,?,?)");

		// Execute query with array of values
		$q3->execute(array($lj_gene_name, $email, $gene, $literature, $ver));

		// Get results
		if($q3->rowCount() > 0) {
			return $response->withStatus(200);
		} else {
			return $response
				->withStatus(500)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 500,
					'message' => 'Gene namge suggestion failed to be submitted.',
				)));
		}

	} catch(PDOException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'code' => $e->getCode(),
				'message' => $e->getMessage(),
				'more_info' => DOMAIN_NAME . WEB_ROOT . '/docs/errors/pdo-exception'
			),JSON_UNESCAPED_SLASHES));
	}
});

?>