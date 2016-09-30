<?php

namespace LotusBase\PhyAlign;

/* PhyAlign\Data */
class Data {

	// Public function: Set job ID
	public function set_job_id($jobID) {
		$this->_jobID = $jobID;
	}

	// Public function: Execute submission
	public function execute() {

		$ch = curl_init();

		// Make GET request
		curl_setopt($ch, CURLOPT_URL, "http://www.ebi.ac.uk/Tools/services/rest/clustalo/status/".$this->_jobID);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Execute and receive server response
		$response = curl_exec($ch);
		curl_close($ch);

		// Construct response
		$_response = array();

		// Parse server response
		switch(strtolower($response)) {
			case 'running':
				$_response['status'] = 1;
				return $response;
				break;

			case 'finished':
				// Job has finished
				$_response['status'] = 0;

				// Get more job data
				$ch = curl_init();

				// Make GET request for the result types available
				curl_setopt($ch, CURLOPT_URL, 'http://www.ebi.ac.uk/Tools/services/rest/clustalo/resulttypes/'.$this->_jobID);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				
				// Execute, receive, and parse server response
				$clustalo_resultTypes = new \SimpleXMLElement(curl_exec($ch));
				curl_close($ch);

				// Get list of result types
				foreach (json_decode(json_encode($clustalo_resultTypes), true)['type'] as $key => $type) {

					if($type['identifier'] === 'sequence') {
						continue;
					}

					$ch = curl_init();

					// Make GET request for each result type
					curl_setopt($ch, CURLOPT_URL, 'http://www.ebi.ac.uk/Tools/services/rest/clustalo/result/'.$this->_jobID.'/'.$type['identifier']);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

					// Execute, receive, and parse server response
					$clustalo_resultData = curl_exec($ch);
					curl_close($ch);

					$_response['data'][] = array(
						'type' => $type,
						'url' => 'http://www.ebi.ac.uk/Tools/services/rest/clustalo/result/'.$this->_jobID.'/'.$type['identifier'],
						'content' => $clustalo_resultData
						);
				}

				return $_response;
				break;

			case 'error':
				throw new \Exception('An error occurred when attempting to retrieve job status.', 500);
				break;

			case 'failure':
				throw new \Exception('The job has failed to run. Unfortunately, no further information is available.', 500);
				break;

			case 'not_found':
				throw new \Exception('The job cannot be found. An invalid job ID has been provided, or the job has expired.', 404);
				break;

			default:
				throw new \Exception('No response received from the EMBL-EBI server.', 500);
				break;
		}
	}
}

?>