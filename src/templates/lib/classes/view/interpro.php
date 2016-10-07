<?php

namespace LotusBase\View;

/* View\Interpro */
class Interpro {

	private $_vars = array(
		'fields' => array('GO','description','name','type','INTERPRO_PARENT','PDBe','PDBeMotif','PFAM','PUBMED','domain_source','SUPERFAMILY')
		);

	// Public function: Set fields
	public function set_fields($fields) {
		if(is_array($fields)) {
			$this->_vars['fields'] = $fields;
		} else {
			$this->_vars['fields'] = explode(',', $fields);
		}
	}

	// Public function: Set Interpro IDs
	public function set_ids($ids) {
		if(is_array($ids)) {
			$this->_vars['ids'] = $ids;
		} else {
			$this->_vars['ids'] = explode(',', $ids);
		}
	}

	// Public function: Execute submission
	public function get_data() {

		$ch = curl_init();

		// Make GET request
		curl_setopt($ch, CURLOPT_URL, 'http://www.ebi.ac.uk/ebisearch/ws/rest/interpro/entry/'.implode(',', $this->_vars['ids']).'?format=json&fields='.implode(',',$this->_vars['fields']));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Execute and receive server response
		$response = json_decode(curl_exec($ch), true);
		curl_close($ch);

		// Since Interpro IDs are unique, use them as keys
		$_go = array();
		foreach($response['entries'] as $entry) {
			$_response[$entry['id']] = $entry;
			if(!empty($entry['fields']['GO'])) {
				foreach($entry['fields']['GO'] as $go) {
					if(!in_array($go, $_go)) {
						$_go[] = $go;
					}
				}
			}
		}

		return array(
			'data' => $_response,
			'go_annotations' => $_go
			);

	}
}

?>