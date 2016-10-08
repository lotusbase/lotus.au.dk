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
		$ebeye_handler->set_ids($id);
		$data = $ebeye_handler->get_data();

		if(!$data) {
			throw new \Exception('No data returned', 400);
		}

		// Return response
		return $response
			->withStatus(200)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 200,
				'data' => $data[$id]
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
			dompred.Evalue AS Evalue,
			dompred.InterproID AS InterproID
		FROM domain_predictions AS dompred
		WHERE dompred.Transcript = ?
		ORDER BY DomainStart ASC
			");
		$q->execute(array($id));

		if($q->rowCount() > 0) {
			while($r = $q->fetch(PDO::FETCH_ASSOC)) {
				$row[] = $r;
			}
		} else {
			throw new \Exception('No entries are found matching the protein or transcript ID provided.', 404);
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