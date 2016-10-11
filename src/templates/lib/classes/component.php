<?php

namespace LotusBase\Component;

/* Component\Dropdown */
class Dropdown {

	private $_vars = array();

	// Public function: Set data
	public function set_data($data) {
		$this->_vars['data'] = $data;
	}

	// Public function: Set title
	public function set_title($title) {
		$this->_vars['title'] = $title;
	}

	// Public function: Set title counter
	public function set_title_counter($num) {
		$this->_vars['title_counter'] = $num;
	}

	// Private function: Get list
	private function get_list() {

		// Data check
		if (!$this->_vars['data'] || empty($this->_vars['data']) || !is_array($this->_vars['data'])) {
			throw new \Exception('No dropdown data detected. Please provide an array');
		}

		// Loop through data to generate markup
		foreach($this->_vars['data'] as $item) {
			$list[] = '<li><a href="'.$item['link'].'"><span class="'.($item['class'] ? $item['class'] : '').'">'.$item['text'].'</span></a></li>';
		}

		return implode('', $list);
	}

	// Public function: Get HTML
	public function get_html() {

		$out = '<div class="dropdown button">
			<span class="dropdown--title">'.$this->_vars['title'].($this->_vars['title_counter'] ? ' ('.$this->_vars['title_counter'].')' : '').'</span>
			<ul class="dropdown--list">'.$this->get_list().'</ul>
		</div>';

		return $out;
	}
}

?>