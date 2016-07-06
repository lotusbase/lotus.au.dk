<?php

namespace LotusBase;

/* SiteSearchForm */
class SiteSearchForm {

	// Counter
	public static $counter = 0;

	// Default variables
	private $form_data = array();
	public function __construct() {

		// Counter
		self::$counter++;

		// Form data
		$this->form_data['id'] = '';
		$this->form_data['default_type'] = 'general';
		$this->form_data['content_before'] = '';
		$this->form_data['options'] = array(
			'general' => array(
				'placeholder'	=> 'Enter search query here',
				'text'			=> 'General',
				'query_var'		=> 'q',
				'action'		=> '/search'
				),
			'lore1' => array(
				'placeholder'	=> 'Enter LORE1 line (e.g. 30000001)',
				'text'			=> 'LORE1',
				'query_var'		=> 'pid',
				'action'		=> '/lore1/search',
				'params'		=> array(
									'v'		=> '3.0'
									)
				),
			'gene' => array(
				'placeholder'	=> 'Enter gene name/ID',
				'text'			=> 'Gene/Transcript',
				'query_var'		=> 'ids',
				'action'		=> '/tools/trex'
				)
			);

	}

	// Set ID
	public function set_id($form_id) {
		$this->form_data['id'] = $form_id;
	}

	// Set default type
	public function set_default_type($type) {
		$this->form_data['default_type'] = $type;
	}

	// Set placeholders
	public function update_options($array) {
		$this->form_data['options'] = array_replace_recursive($this->form_data['options'], $array);	
	}

	// Set before content
	public function set_content_before($content) {
		$this->form_data['content_before'] = $content;
	}

	// Generate form
	private function generate_form() {
		$default = $this->form_data['options'][$this->form_data['default_type']];
		return '<form method="get" class="search-form" action="'.WEB_ROOT.$default['action'].'"'.(!empty($this->form_data['id']) ? ' id="'.$this->form_data['id'].'"': '').'>
			'.$this->form_data['content_before'].'
			<input type="search" name="'.$default['query_var'].'" placeholder="'.$default['placeholder'].'" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" />
			'.$this->generate_form_options().'
			'.$this->generate_form_hidden_inputs().'
			<button type="submit"><span class="pictogram icon-search icon--no-spacing"></span></button>
		</form>';
	}

	// Generate form options
	private function generate_form_options() {
		$select = '<select class="qtype" id="qtype-'.self::$counter.'"><option disabled value="">Select a search type</option>';
		foreach ($this->form_data['options'] as $type => $data) {
			$select .= '<option
				value="'.$type.'"
				data-input-placeholder="'.$data['placeholder'].'"
				data-form-query-var="'.$data['query_var'].'"
				data-form-action="'.WEB_ROOT.$data['action'].'"
				data-form-params=\''.(isset($data['params']) && !empty($data['params']) ? json_encode($data['params']) : '').'\'>'.$data['text'].'</option>';
		}
		$select .= '</select>';

		return $select;
	}

	// Generate hidden inputs
	private function generate_form_hidden_inputs() {
		if (isset($this->form_data['options'][$this->form_data['default_type']]['params'])) {
			foreach ($this->form_data['options'][$this->form_data['default_type']]['params'] as $key => $value) {
				$params .= '<input class="search-form__param" type="hidden" name="'.$key.'" value="'.$value.'" />';
			}
			return $params;
		}
	}

	// Echo form
	public function get_form() {
		$form = $this->generate_form();
		return $form;
	}
}
?>