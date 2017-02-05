<?php

namespace LotusBase\ExpAt;
use \PDO;

/* ExpAt\Dataset*/
class Dataset {

	// Default settings for select element
	private $_vars = array(
		'id' => 'expat-dataset',
		'name' => 'dataset',
		'blacklist' => array()
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
			'label' => 'Marco Giovanetti, RNAseq data (2015)'
			),
		'rnaseq-eiichimurakami-2016' => array(
			'idType' => 'transcriptid',
			'experiment' => 'rnaseq-eiichimurakami-2016',
			'value' => 'rnaseq-eiichimurakami-2016',
			'text' => 'Nod factor treatment of L. japonicus',
			'intranet_only' => true,
			'label' => 'Eiichi Murakami, RNAseq data (2016)'
			),
		'rnaseq-handay-2015' => array(
			'idType' => 'geneid',
			'experiment' => 'rnaseq-handay-2015',
			'value' => 'rnaseq-handay-2015',
			'text' => 'Inoculation of L. japonicus with Rhizophagus irregularis, an AM fungi',
			'intranet_only' => false,
			'label' => 'Handa, Y. et al., RNAseq data (2015)'
			),
		'rnaseq-sasakit-2014' => array(
			'idType' => 'geneid',
			'experiment' => 'rnaseq-sasakit-2014',
			'value' => 'rnaseq-sasakit-2014',
			'text' => 'Shoot of L. japonicus MG20 WT, har1, CLE-RS1/2 overexpression.',
			'intranet_only' => false,
			'label' => 'Sasaki, T. et al., RNAseq data (2014)'
			),
		'rnaseq-suzakit-2014' => array(
			'idType' => 'geneid',
			'experiment' => 'rnaseq-suzakit-2014',
			'value' => 'rnaseq-suzakit-2014',
			'text' => 'Endoreduplication-mediated initiation of symbiotic organ development in Lotus japonicus',
			'intranet_only' => false,
			'label' => 'Suzaki, T. et al., RNAseq data (2014)'
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

	// Set blacklist
	public function set_blacklist($ids) {
		$this->_vars['blacklist'] = $ids;
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
						(
							!isset($d['intranet_only']) ||
							(
								isset($d['intranet_only']) &&
								$d['intranet_only'] === !!is_allowed_access('/expat/')
							)
						) &&
						(
							(
								!empty($this->_vars['idType']) &&
								in_array($d['idType'], $this->_vars['idType'])
							) ||
							empty($this->_vars['idType'])
						) &&
						(
							!in_array($dataset, $this->_vars['blacklist'])
						)
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