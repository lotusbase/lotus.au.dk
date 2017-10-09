<?php

namespace LotusBase\Getter;

class Base {

	// Private variables
	protected $_vars = array();

	// Private function: JSON
	protected function get_request($query) {

		// Make GET request
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $query);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$_response = json_decode(curl_exec($ch), true);

		// Close connection
		curl_close($ch);

		// Return response
		return $_response;
	}

}

/* Getter\PMID */
class PMID extends Base {

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

/* Getter\DOI */
class DOI extends Base{

	// Public function: set_doi
	public function set_doi($doi) {
		if(!is_array($doi)) {
			$doi = explode(',', preg_replace('/\s/', '', $doi));
		}
		$this->_vars = array('DOI' => $doi);
	}

	// Public function get_data
	public function get_data() {
		$output = array();
		foreach($this->_vars['DOI'] as $doi) {

			$ref = $this->get_request('https://api.crossref.org/works/'.$doi.'?mailto=hello@lotus.au.dk');
			$output[$doi] = $ref['message'];
		}

		return $output;
	}
}

?>