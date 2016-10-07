<?php

namespace LotusBase\View;

/* View\GeneOntology */
class GeneOntology {

	private $_vars = array(
		'format' => 'json',
		'fields' => array('namespace','description')
		);

	// Public function: Set fields
	public function set_fields($fields) {
		if(is_array($fields)) {
			$this->_vars['fields'] = $fields;
		} else {
			$this->_vars['fields'] = explode(',', $fields);
		}
	}

	// Public function: Set gene ontology IDs
	public function set_ids($ids) {
		if(is_array($ids)) {
			$this->_vars['ids'] = $ids;
		} else {
			$this->_vars['ids'] = explode(',', $ids);
		}
	}

	// Public function: Set format
	public function set_format($format) {
		if(in_array(strtolower($format), array('json','xml'))) {
			$this->_vars['format'] = strtolower($format);
		}
	}

	// Public function: Execute submission
	public function get_data() {

		$ch = curl_init();

		// Make GET request
		curl_setopt($ch, CURLOPT_URL, 'http://www.ebi.ac.uk/ebisearch/ws/rest/go/entry/'.implode(',', $this->_vars['ids']).'?format='.$this->_vars['format'].'&fields='.implode(',', $this->_vars['fields']));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Execute and receive server response
		$response = json_decode(curl_exec($ch), true);
		curl_close($ch);

		// Since Interpro IDs are unique, use them as keys
		foreach($response['entries'] as $entry) {
			$_response[$entry['id']] = $entry;
		}

		return $_response;

	}
}

?>