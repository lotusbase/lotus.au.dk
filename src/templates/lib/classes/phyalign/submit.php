<?php

namespace LotusBase\PhyAlign;

/* PhyAlign\Submit */
class Submit {

	// Public function: Set data
	public function set_data($data) {
		$this->_data = $data;
	}

	// Public function: Execute submission
	public function execute() {

		$ch = curl_init();

		// Make POST request
		curl_setopt($ch, CURLOPT_URL, 'http://www.ebi.ac.uk/Tools/services/rest/clustalo/run/');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Execute and receive server response
		$response = curl_exec($ch);
		curl_close($ch);

		// Parse server response
		return $response;
	}

}

?>