<?php

namespace LotusBase\View\GO;

/* View\GO\Link */
class Link {

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

/* View\GO\Metadata */
class Metadata {

	private $_vars = array();

	// Public function: Set field
	public function set_field($field) {
		$this->_vars['field'] = $field;
	}

	// Public function: Set value
	public function set_value($value) {
		$this->_vars['value'] = $value;
	}

	// Public function: Get HTML
	public function get_html() {
		$field = $this->_vars['field'];
		$value = $this->_vars['value'];

		switch (strtolower($field)) {
			case 'isbn':
				// Retrieve JSON file from Google Books API
				$ch = curl_init();

				// Make GET request
				curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/books/v1/volumes?q=isbn:0198506732');
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

				// Execute and receive server response
				$book = json_decode(curl_exec($ch), true)['items'][0];
				curl_close($ch);

				// Variables
				$title = $book['volumeInfo']['title'];
				$link = $book['volumeInfo']['canonicalVolumeLink'];
				$img_src = $book['volumeInfo']['imageLinks']['thumbnail'];
				$authors = implode(',', $book['volumeInfo']['authors']);
				$publisher = $book['volumeInfo']['publisher'];
				$date = $book['volumeInfo']['publishedDate'];
				$pages = $book['volumeInfo']['pageCount'];
				$description = $book['volumeInfo']['description'];

				// Generate HTML
				$out = '<div class="book"><h4 class="book__title">'.$title.'</h4>';
				$out .= '<img src="'.$img_src.'" alt="'.$title.'" title="'.$title.'" class="float--left" />';
				$out .= '<p><span class="book__authors">'.$authors.'</span> &middot; <a href="'.$link.'" tilte="View more information on Google Books" class="button button--small"><span class="icon-link-ext">More information on Google Books</span></a></p>';
				$out .= '<p><span class="book__publisher">'.$publisher.'</span>, <span class="book__date">'.$date.'</span> &middot; <span class="book__pages">'.$pages.' pages</span></p>';
				$out .= '<p class="book__description">'.$description.'</p></div>';

				// Return
				return $out;
				break;
			
			default:
				return $value;
				break;
		}
		
	}

}

?>