<?php
// View API
$api->get('/view', function ($request, $response) {
	$response->write('Welcome to Lotus base view API v1');
	return $response;
});

// Get domain description
$api->get('/view/domain[/{source}/{id}]', function($request, $response, $args) {
	try {

		if(!empty($args['source'])) {
			$source = $args['source'];
		} else if(!empty($request->getParam('source'))) {
			$source = $request->getParam('source');
		} else {
			throw new \Exception('Source is missing from request. Both source and ID must be provided.', 400);
		}

		if(!empty($args['id'])) {
			$id = $args['id'];
		} else if(!empty($request->getParam('id'))) {
			$id = $request->getParam('id');
		} else {
			throw new \Exception('ID is missing from request. Both source and ID must be provided.', 400);
		}

		// Retrieve description from EB-eye service
		$ebeye_handler = new \LotusBase\EBI\EBeye();
		$ebeye_handler->set_domain($source);
		$ebeye_handler->set_ids(explode(',', $id));
		$data = $ebeye_handler->get_data();

		if(!$data) {
			throw new \Exception('No data returned', 400);
		} else {
			if(count($data) === 1) {
				$_data = $data[$id];
			} else {
				$_data = $data;
			}
		}

		// Return response
		return $response
			->withStatus(200)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 200,
				'data' => $_data
				)));

	} catch(\Exception $e) {
		return $response
			->withStatus($e->getCode())
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => $e->getCode(),
				'message' => $e->getMessage()
				)));
	}
});

// Get domain data
$api->get('/view/domains[/{id}]', function($request, $response, $args) {
	try {

		if(!empty($args['id'])) {
			$id = $args['id'];
		} else if(!empty($request->getParam('id'))) {
			$id = $request->getParam('id');
		} else {
			throw new \Exception('Transcript or protein ID is missing from request.', 400);
		}

		// Database connection and query
		$db = $this->get('db');
		$q = $db->prepare("SELECT
			dompred.Source AS Source,
			dompred.SourceID AS SourceID,
			dompred.DomainStart AS DomainStart,
			dompred.DomainEnd AS DomainEnd,
			dompred.DomainEnd - dompred.DomainStart AS DomainLength,
			dompred.Evalue AS Evalue,
			dompred.InterProID AS InterProID,
			dommeta.SourceDescription AS SourceDescription
		FROM domain_predictions AS dompred
		LEFT JOIN domain_metadata AS dommeta ON dompred.SourceID = dommeta.SourceID
		WHERE dompred.Transcript = ?
		ORDER BY DomainStart ASC
			");
		$q->execute(array($id));

		if($q->rowCount() > 0) {
			while($r = $q->fetch(PDO::FETCH_ASSOC)) {
				$row[] = $r;

				// Also collect Interpro IDs
				if($r['InterProID']) {
					$ips[] = $r['InterProID'];
				}
			}
		} else {
			throw new \Exception('No entries are found matching the protein or transcript ID provided.', 404);
		}

		// Merge InterPro data with current result set
		$ip_handler = new \LotusBase\EBI\EBeye();
		$ip_handler->set_domain('interpro');
		$ip_handler->set_ids(array_values(array_unique($ips)));
		$ip_data = $ip_handler->get_data();

		// Collect type
		$ip_data_itemised = array(
			'type' => array()
			);
		foreach($ip_data as $ip) {
			$ip_data_itemised['type'][$ip['id']] = str_replace('_', ' ', $ip['fields']['type'][0]);
		}

		// Merge
		foreach($row as &$r) {
			if($r['InterProID']) {
				$r['InterProType'] = $ip_data_itemised['type'][$r['InterProID']];
			} else {
				$r['InterProType'] = null;
			}
		}

		// Return response
		return $response
			->withStatus(200)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 200,
				'data' => $row
				)));

	} catch(\Exception $e) {
		return $response
			->withStatus($e->getCode())
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => $e->getCode(),
				'message' => $e->getMessage()
				)));
	}
});

?>