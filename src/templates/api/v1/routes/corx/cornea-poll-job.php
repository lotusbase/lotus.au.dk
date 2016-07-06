<?php

// Poll CORNEA jobs
$api->get('/cornea/job/status/{id}', function ($request, $response, $args) {
	try {
		
		$db = $this->get('db');

		// Hash ID check
		$job_hash_id = $args['id'];
		if(!(preg_match('/((cli|standard)_)?[A-Fa-f0-9]{32}/', $job_hash_id))) {
			throw new Exception('Job ID provided is not a 32-character hexadecimal string.', 400);
		}

		$q = $db->prepare('SELECT * FROM correlationnetworkjob WHERE hash_id = :hash_id LIMIT 1');
		$q->bindParam(':hash_id', $job_hash_id);
		$q->execute();

		// Retrieve result
		$result = $q->fetch(PDO::FETCH_ASSOC);

		if($result) {

			// Construct data
			if($result['status'] === 3) {
				// Check if file exists
				if(!file_exists('/var/www/html/data/cornea/jobs/'.$job_hash_id.'.json.gz')) {
					throw new Exception('Unable to retrieve generated JSON file for the job. This is a server error&mdash;please open an issue with us.', 500);
				} else {
					$result['filesize'] = human_filesize(filesize('/var/www/html/data/cornea/jobs/'.$job_hash_id.'.json.gz'));
				}
			}

			// Get queue
			$queue_ping = json_decode(exec(PYTHON_PATH.' '.DOC_ROOT.'/lib/corx/CorrelationNetworkClient.py queue '.$job_hash_id), true);
			if(!empty($queue_ping)) {
				$result['queuesize'] = count($queue_ping);
			} else {
				$result['queuesize'] = 0;
			}

			// Return data, removing keys that have empty values
			return $response
				->withStatus(200)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 200,
					'data' => array_filter($result, 'strlen')
					))
				);
		} else {
			return $response
				->withStatus(404)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 404,
					'message' => 'Job does not exist.'
					))
				);
		}

	} catch(PDOException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'code' => $e->getCode(),
				'message' => $e->getMessage()
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