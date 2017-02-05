<?php

namespace LotusBase\Getter;

/* Getter\PMID */
class PMID {

	// Private variables
	private $_vars = array();

	// Private function: JSON
	private function get_request($query) {

		// Make GET request
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $query);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$_response = json_decode(curl_exec($ch), true);

		// Close connection
		curl_close($ch);

		return $_response;

	}

	// Public function: set_pmid
	public function set_pmid($pmid) {
		if(!is_array($pmid)) {
			$pmid = explode(',', preg_replace('/\s/', '', $pmid));
		}
		$this->_vars = array('PMID' => $pmid);
	}

	// Public function: get_data
	public function get_data() {
		$ref = $this->get_request('https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=pubmed&retmode=json&id='.implode(',', $this->_vars['PMID']));
		return $ref['result'];
	}
}

?>