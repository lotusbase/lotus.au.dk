<?php

namespace LotusBase\EBI;

/* EBI\EBeye */
class EBeye {

	private $_vars = array(
		'format' => 'json',
		'fields' => array(
			'go' => array('namespace','description'),
			'interpro' => array('GO','description','name','type','INTERPRO_PARENT','PDBe','PDBeMotif','PFAM','PUBMED','domain_source','SUPERFAMILY'),
			'pfam' => array('description','type','process','function','PUBMED','domain_source')
			),
		'id' => array(
			'go' => 'id',
			'interpro' => 'id',
			'pfam' => 'acc')
		);

	// Public function: Set domain
	public function set_domain($domain) {
		if(array_key_exists($domain, $this->_vars['fields'])) {
			$this->_vars['domain'] = $domain;
		} else {
			throw new \Exception('Invalid domain selected. Valid domains are: '.implode(',', array_keys($this->_vars['fields'])));
		}
	}

	// Public function: Set fields
	public function set_fields($fields) {
		if(is_array($fields)) {
			$this->_vars['fields'][$this->_vars['domain']] = $fields;
		} else {
			$this->_vars['fields'][$this->_vars['domain']] = explode(',', $fields);
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
		curl_setopt($ch, CURLOPT_URL, 'http://www.ebi.ac.uk/ebisearch/ws/rest/'.$this->_vars['domain'].'/entry/'.implode(',', $this->_vars['ids']).'?format='.$this->_vars['format'].'&fields='.implode(',', $this->_vars['fields'][$this->_vars['domain']]));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Execute and receive server response
		$response = json_decode(curl_exec($ch), true);
		$_response = array();
		curl_close($ch);

		// Since Interpro IDs are unique, use them as keys
		foreach($response['entries'] as $entry) {
			$_response[$entry[$this->_vars['id'][$this->_vars['domain']]]] = $entry;

			// Map keys
			if($this->_vars['domain'] === 'pfam') {
				foreach($_response as &$e) {
					$e['short_name'] = $e['id'];
					$e['id'] = $e['acc'];
					unset($e['acc']);
				}
			}
		}

		return $_response;

	}
}

?>