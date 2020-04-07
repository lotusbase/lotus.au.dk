<?php

namespace LotusBase\ExpAt;
use \PDO;

/* ExpAt\Dataset*/
class Dataset {

	// Construct
	function __construct() {

		// Default settings for select element
		$this->_vars = array(
			'id' => 'expat-dataset',
			'name' => 'dataset',
			'blacklist' => array()
		);

		// Initialize optgroups
		$this->_optgroups = array();

		// Options
		$this->_opts = array();

		// Database connection
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		// Query 1: Collect all PMID
		$q1 = $db->prepare('SELECT
			t1.Species AS Species,
			t1.IDType AS IDType,
			t1.ColumnShare AS ColumnShare,
			t1.Experiment AS Experiment,
			t1.Dataset AS Dataset,
			t1.Text AS Text,
			t1.Label AS Label,
			GROUP_CONCAT(t2.UserGroup) AS UserGroups
		FROM expat_datasets AS t1
		LEFT JOIN expat_datasets_usergroup AS t2 ON
			t1.IDKey = t2.DatasetIDKey
		GROUP BY t1.IDKey
		ORDER BY t1.LabelSort');
		$q1->execute();

		if($q1->rowCount()) {
			while($row = $q1->fetch(PDO::FETCH_ASSOC)) {
				$this->_opts[$row['Dataset']] = array(
					'species' => $row['Species'],
					'genome' => $row['Genome'],
					'idType' => $row['IDType'],
					'column_share' => $row['ColumnShare'],
					'experiment' => $row['Experiment'],
					'value' => $row['Dataset'],
					'text' => $row['Text'],
					'label' => $row['Label'],
					'user_groups' => array_filter(explode(',', $row['UserGroups']))
					);
			}
		}
	}

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

	// Set species
	public function set_species($species) {
		if(!empty($species)) {
			$this->_vars['species'] = $species;
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
					$userGroups = $d['user_groups'];
					if(
						(
							empty($userGroups) || is_allowed_access_by_user_group($userGroups)
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
						) &&
						(
							empty($this->_vars['species']) || $this->_vars['species'] === $d['species']
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