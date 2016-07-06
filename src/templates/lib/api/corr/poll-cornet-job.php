<?php

try {

	// Hash ID check
	$job_hash_id = $_GET['job'];
	if(!(preg_match('/(cli_)?[A-Fa-f0-9]{32}/', $job_hash_id))) {
		throw new Exception('Job ID provided is not a 32-character hexadecimal string.');
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
			if(!file_exists('/var/www/html/data/cornet/jobs/'.$_GET['job'].'.json.gz')) {
				throw new Exception('Unable to retrieve generated JSON file for the job. This is a server error&mdash;please open an issue with us.');
			} else {
				$result['filesize'] = human_filesize(filesize('/var/www/html/data/cornet/jobs/'.$_GET['job'].'.json.gz'));
			}
		}

		// Get queue
		$queue_ping = json_decode(exec(PYTHON_PATH.' ./lib/python/corr/CorrelationNetworkClient.py queue '.$job_hash_id), true);
		if(!empty($queue_ping)) {
			$result['queuesize'] = count($queue_ping);
		} else {
			$result['queuesize'] = 0;
		}

		// Return data, removing keys that have empty values
		$dataReturn->set_data(array_filter($result, 'strlen'));
		$dataReturn->execute();
	} else {
		$error->set_message('Job does not exist.');
		$error->set_status(404);
		$error->execute();
	}

} catch(PDOException $e) {
	$error->set_message('We have encountered an error: '.$e->getMessage());
	$error->execute();
} catch(Exception $e) {
	$error->set_message('We have encountered an error: '.$e->getMessage());
	$error->execute();
}

?>