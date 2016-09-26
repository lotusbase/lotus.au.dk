<?php
// PhyAlign API
$api->get('/phyalign', function ($request, $response) {
	$response->write('Welcome to Lotus base PhyAlign API v1');
	return $response;
});

// Submit jobs to EMBL-EBI server
$api->post('/phyalign/submit', function($request, $response, $args) {
	try {
		$p = $request->getParsedBody();

		// Submit data to EMBL-EBI server
		$clustalo_submit = new \LotusBase\PhyAlign\Submit;
		$clustalo_submit->set_data($p);
		$clustalo_response = $clustalo_submit->execute();

		if(!$clustalo_response) {
			throw new Exception('No response returned from EMBL-EBI server', 500);
		}

		// Return response
		return $response
			->withStatus(200)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 200,
				'data' => array(
					'jobID' => $clustalo_response
					)
				)));

	} catch(Exception $e) {
		return $response
			->withStatus($e->getCode())
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => $e->getCode(),
				'message' => $e->getMessage()
				)));
	}
});

// Retrieve job status and data
$api->get('/phyalign/data[/{jobID}]', function($request, $response, $args) {
	try {

		if(!empty($args['jobID'])) {
			$jobID = $args['jobID'];
		} else if(!empty($request->getParam('jobID'))) {
			$jobID = $request->getParam('jobID');
		} else {
			throw new \Exception('No job ID has been provided.', 400);
		}

		// Check job status with
		$clustalo_status = new \LotusBase\PhyAlign\Data;
		$clustalo_status->set_job_id($jobID);
		$clustalo_response = $clustalo_status->execute();

		if(empty($clustalo_response)) {
			throw new Exception('No response returned from EMBL-EBI server', 500);
		}

		// Return response
		return $response
			->withStatus(200)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 200,
				'data' => $clustalo_response
				)));

	} catch(Exception $e) {
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