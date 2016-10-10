<?php

namespace LotusBase\View;

/* View\GOLink */
class GOLink {

	private $_vars = array(
		'external_links' => array(
			'AmiGO' => 'http://amigo.geneontology.org/amigo/medial_search?q={{id}}',
			'InterPro' => 'http://www.ebi.ac.uk/interpro/search?q={{id}}',
			'QuickGO (legacy)' => 'http://www.ebi.ac.uk/QuickGO/GTerm?id={{id}}',
			'QuickGO (beta)' => 'http://www.ebi.ac.uk/QuickGO-Beta/term/{{id}}'
			),
		'internal' => false
		);

	// Public function: Set ID
	public function set_id($id) {
		$this->_vars['id'] = $id;
	}

	// Public function: Add internal view link
	public function add_internal_link() {
		$this->_vars['internal'] = true;
	}

	// Private function: Generate URL
	private function generate_url($id, $template) {
		return str_replace('{{id}}', $id, $template);
	}

	// Public function: Get HTML
	public function get_html() {

		$id = $this->_vars['id'];
		$out = '';

		// If internal view link is requested
		if($this->_vars['internal']) {
			$out .= '<li><a href="'.WEB_ROOT.'/view/go/'.$id.'" title="View '.$id.' on Lotus Base"><span class="icon-eye">View details of '.$id.'</span></a></li>';
		}

		// Append external links
		foreach($this->_vars['external_links'] as $name => $url) {
			$out .= '<li><a href="'.$this->generate_url($id, $url).'" target="_blank" title="View '.$id.' on '.$name.'">'.$name.'</a></li>';
		}

		// Return output
		return $out;
	}

}

?>