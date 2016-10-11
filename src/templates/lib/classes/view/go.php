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

	// Private function: JSON
	private function get_request($query, $source_format = 'json', $output_format = 'object') {

		// Make GET request
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $query);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		// Receive server response
		if($source_format === 'xml') {
			$_response = new \SimpleXMLElement(curl_exec($ch));
		} else if($source_format === 'json') {
			$_response = json_decode(curl_exec($ch), true);
		}

		echo curl_error($ch);

		// Close connection
		curl_close($ch);

		// Parse server response
		if($output_format === 'object') {
			$response = $_response;
		} else if($output_format === 'json') {
			$response = json_encode($_response);
		} else {
			throw new \Exception('Invalid format selected');
		}

		// Return
		return $response;
	}

	// Public function: Get HTML
	public function get_html() {
		$field = $this->_vars['field'];
		$value = $this->_vars['value'];

		switch (strtolower($field)) {
			case 'isbn':
				
				// Get book data from Google Books API
				$book = $this->get_request('https://www.googleapis.com/books/v1/volumes?q=isbn:'.$value)['items'][0];

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
				$out = '<div class="media media__book">';
				$out .= '<span class="media__title book__title">'.$title.'</span>';
				$out .= '<img src="'.$img_src.'" alt="'.$title.'" title="'.$title.'" class="float--left" />';
				$out .= '<p><span class="book__authors">'.$authors.'</span> &middot; <a href="'.$link.'" tilte="View more information on Google Books" class="button button--small"><span class="icon-link-ext">ISBN: <span class="media__id">'.$value.'</span></span></a></p>';
				$out .= '<p><span class="book__publisher">'.$publisher.'</span>, <span class="book__date">'.$date.'</span> &middot; <span class="book__pages">'.$pages.' pages</span></p>';
				$out .= '<p class="book__description">'.$description.'</p>';
				$out .= '</div>';

				// Return
				return $out;
				break;

			case 'pmid':

				// Get publication data from NCBI API
				$pub = $this->get_request('https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=pubmed&retmode=json&id='.$value);
				$authors = array_map(function($a) {
					$author = $a['name'];
					return '<a href="https://www.ncbi.nlm.nih.gov/pubmed/?term='.$author.'[Author]&cauthor=true&cauthor_uid='.$value.'"><span class="icon-link-ext"></span>'.$author.'</a>';
				}, $pub['result'][$value]['authors']); 

				// Get abstract
				$pub_abs = $this->get_request('https://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&rettype=abstract&id='.$value, 'xml');
				$abstract = $pub_abs->PubmedArticle->MedlineCitation->Article->Abstract->AbstractText;

				// Generate HTML
				$out = '<div class="media media__article">';
				$out .= '<span class="media__title article__title">'.$pub['result'][$value]['title'].'</span>';
				$out .= '<span class="article__authors">'.implode(', ', $authors).'</span><br />';
				$out .= '<span class="article__journal">'.$pub['result'][$value]['source'].'</span>. ';
				$out .= '<span class="article__date">'.$pub['result'][$value]['pubdate'].'</span>; ';
				$out .= '<span class="article__volume">'.$pub['result'][$value]['volume'].'</span> (<span class="article__issue">'.$pub['result'][$value]['issue'].'</span>): ';
				$out .= '<span class="article__pages">'.preg_replace('#\p{Pd}#u', '&ndash;', $pub['result'][$value]['pages']).'</span>.';
				$out .= '<a href="https://www.ncbi.nlm.nih.gov/pubmed/'.$value.'" class="button button--small" tilte="View more information on NCBI PubMed"><span class="icon-link-ext">PMID: <span class="media__id">'.$value.'</span></span></a>';
				$out .= '<p class="article__abstract">'.$abstract.'</p>';
				$out .= '</div>';

				return $out;
				break;
			
			default:
				return $value;
				break;
		}
		
	}

}

?>