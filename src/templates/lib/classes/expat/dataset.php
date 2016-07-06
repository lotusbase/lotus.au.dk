<?php

namespace LotusBase\ExpAt;
use \PDO;

/* ExpAt\Dataset*/
class Dataset {

	// Default settings for select element
	private $_vars = array(
		'id' => 'expat-dataset',
		'name' => 'dataset'
		);

	// Initialize optgroups
	private $_optgroups = array();

	// Options
	private $_opts = array(
		'ljgea-geneid' => array(
			'idType' => 'geneid',
			'column_share' => 'ljgea',
			'experiment' => 'ljgea',
			'value' => 'ljgea-geneid',
			'text' => 'Entire LjGEA dataset by gene ID',
			'label' => 'LjGEA'
			),
		'ljgea-probeid' => array(
			'idType' => 'probeid',
			'column_share' => 'ljgea',
			'experiment' => 'ljgea',
			'value' => 'ljgea-probeid',
			'text' => 'Entire LjGEA dataset by probe ID',
			'label' => 'LjGEA'
			),
		'rnaseq-simonkelly-2015-bacteria' => array(
			'idType' => 'transcriptid',
			'experiment' => 'rnaseq-simonkelly-2015',
			'value' => 'rnaseq-simonkelly-2015-bacteria',
			'text' => 'Bacteria treatment by transcript ID',
			'intranet_only' => true,
			'label' => 'Simon Kelly, RNAseq data (2015)'
			),
		'rnaseq-simonkelly-2015-purifiedcompounds' => array(
			'idType' => 'transcriptid',
			'experiment' => 'rnaseq-simonkelly-2015',
			'value' => 'rnaseq-simonkelly-2015-purifiedcompounds',
			'text' => 'Purified compounds treatment by transcript ID',
			'intranet_only' => true,
			'label' => 'Simon Kelly, RNAseq data (2015)'
			),
		'rnaseq-marcogiovanetti-2015-am' => array(
			'idType' => 'probeid',
			'experiment' => 'rnaseq-marcogiovanetti-2015',
			'value' => 'rnaseq-marcogiovanetti-2015-am',
			'text' => 'C. trifolii germinating spore exudates by probe ID',
			'intranet_only' => true,
			'label' => 'Marco Giovanetti, RNAseq data (2015)'
			)
		);

	// PRIVATE
	// Get all option groups by label
	private function _get_optgroups() {
		foreach ($this->_opts as $dataset => $d) {
			if(!in_array($d['label'], $this->_optgroups)) {
				$this->_optgroups[] = $d['label'];
			}
		}
	}
	
	// PUBLIC
	// Set filtering
	public function set_idType($idType) {
		$this->_vars['idType'] = $idType;
	}

	// Set dataset
	public function set_dataset($dataset) {
		if(!empty($dataset)) {
			$this->_vars['dataset'] = $dataset;
		}
	}

	// Set selected dataset
	public function set_selected_dataset($selected_dataset) {
		if(!empty($selected_dataset)) {
			$this->_vars['selected_dataset'] = $selected_dataset;
		}
	}

	// Set ID
	public function set_id($id) {
		$this->_vars['id'] = $id;
	}

	// Set name
	public function set_name($name) {
		$this->_vars['name'] = $name;
	}

	// Render
	public function render() {
		// Get optgroups
		$this->_get_optgroups();

		// Generate HTML output
		$select_html = '<select id="'.$this->_vars['id'].'" name="'.$this->_vars['name'].'">';
		$select_html .= '<option value="" '.(empty($d['column_share']) && empty($this->_vars['selected_dataset']) ? 'selected' : '').'>Select an experiment dataset</option>';
		foreach ($this->_optgroups as $og) {
			$opts = array();
			foreach ($this->_opts as $dataset => $d) {
				if($d['label'] === $og) {
					if(
						(empty($d['intranet_only']) ||
	 						(
	 							!empty($d['intranet_only']) && $d['intranet_only'] === !!is_intranet_client()
	 							)
	 						) &&
						(
							(
								!empty($this->_vars['idType']) &&
								in_array($d['idType'], $this->_vars['idType']))
							) ||
							empty($this->_vars['idType'])
						) {
						$opts[] = '<option
							data-idtype="'.$d['idType'].'"
							data-column-share="'.(!empty($d['column_share']) ? $d['column_share'] : '').'"
							data-experiment="'.$d['experiment'].'"
							value="'.$d['value'].'" '.((isset($this->_vars['dataset']) && $this->_vars['dataset'] === $d['value']) || (isset($this->_vars['selected_dataset']) && $this->_vars['selected_dataset'] === $d['value']) ? 'selected' : '').'>'.$d['text'].'</option>';
					}
				}
			}
			if(count($opts)) {
				$select_html .= '<optgroup label="'.$og.'">';
				$select_html .= implode('', $opts);
				$select_html .= '</optgroup>';
			}
		}
		
		$select_html .= '</select>';
		return $select_html;
	}

}
?>