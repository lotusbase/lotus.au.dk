<?php

namespace LotusBase\Component;

/* Component\Dropdown */
class Dropdown {

	protected $_vars = array();

	// Public function: Construct
	public function __construct() {
		$this->_vars['attributes'] = array('class' => array('dropdown', 'button'));
	}

	// Public function: Add HTML attributes
	public function add_html_attributes($attrs) {
		foreach ($attrs as $attr => $values) {
			if(!isset($this->_vars['attributes'][$attr])) {
				$this->_vars['attributes'][$attr] = array();
			}

			if(is_array($values)) {
				$this->_vars['attributes'][$attr] = array_merge($this->_vars['attributes'][$attr], $values);
			} else {
				$this->_vars['attributes'][$attr] = array_merge($this->_vars['attributes'][$attr], explode(' ', $values));
			}
		}
	}

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

	// Public function: Set title link
	public function set_title_link($link) {
		$this->_vars['title_link'] = $link;
	}

	// Private function: Get list
	private function get_list() {

		// Data check
		if (!$this->_vars['data'] || empty($this->_vars['data']) || !is_array($this->_vars['data'])) {
			throw new \Exception('No dropdown data detected. Please provide an array');
		}

		// Loop through data to generate markup
		foreach($this->_vars['data'] as $item) {
			$list[] = '<li><a href="'.$item['link'].'" title="'.(!empty($item['title']) ? $item['title'] : strip_tags($item['text'])).'" '.(!empty($item['target']) ? 'target="'.$item['target'].'"' : '').'><span class="'.(!empty($item['class']) ? $item['class'] : '').'">'.$item['text'].'</span></a></li>';
		}

		return implode('', $list);
	}

	// Private function: Get attributes
	private function get_attributes() {

		$attributes = ' ';
		foreach($this->_vars['attributes'] as $attr => $values) {
			$attributes .= $attr.'="'.implode(' ', $values).'"';
		}

		return $attributes;
	}

	// Public function: Get HTML
	public function get_html() {

		$out = '<div '.$this->get_attributes().'>
			<span class="dropdown--title">'.(!empty($this->_vars['title_link']) ? '<a href="'.$this->_vars['title_link'].'" title="'.$this->_vars['title'].'">' : '').$this->_vars['title'].(!empty($this->_vars['title_counter']) ? ' ('.$this->_vars['title_counter'].')' : '').(!empty($this->_vars['title_link']) ? '</a>' : '').'</span>
			<ul class="dropdown--list">'.$this->get_list().'</ul>
		</div>';

		return $out;
	}
}

/* Component\TranscriptDropdown */
class TranscriptDropdown extends Dropdown {

	private $transcript;

	// Public function: Set transcript
	public function set_transcript($transcript) {
		$this->transcript = $transcript;
		$this->set_data(array(
			array(
				'class' => 'icon-eye',
				'link' => WEB_ROOT.'/view/transcript/'.$this->transcript,
				'title' => 'View transcript',
				'text' => 'View transcript'
				),
			array(
				'class' => 'icon-leaf',
				'link' => WEB_ROOT.'/lore1/search-exec?gene='.$this->transcript.'&amp;v=MG20_3.0',
				'title' => 'Search for LORE1 insertions in this transcript',
				'text' => '<em>LORE1</em> insertions in MG20 v3.0'
				),
			array(
				'class' => 'icon-book',
				'link' => WEB_ROOT.'/genome?data=genomes%2Flotus-japonicus%2Fmg20%2Fv3.0&loc='.$this->transcript,
				'title' => 'View transcript in genome browser',
				'text' => 'Genome browser'
				),
			array(
				'class' => 'icon-map',
				'link' => WEB_ROOT.'/expat?ids='.$this->transcript,
				'title' => 'Access expression data from the Expression Atlas (ExpAt)',
				'text' => 'Expression Atlas (ExpAt)'
				)
			)
		);
	}
}

/* Component\GODropdown */
class GODropdown extends Dropdown {

	private $go_term;
	private $internal_link = false;
	private $external_links = array(
		'AmiGO' => 'http://amigo.geneontology.org/amigo/medial_search?q={{id}}',
		'InterPro' => 'http://www.ebi.ac.uk/interpro/search?q={{id}}',
		'QuickGO (legacy)' => 'http://www.ebi.ac.uk/QuickGO/GTerm?id={{id}}',
		'QuickGO (beta)' => 'http://www.ebi.ac.uk/QuickGO-Beta/term/{{id}}'
		);

	// Public function: Set GO term
	public function set_go_term($go_term) {
		$this->go_term = $go_term;
		$this->set_data($this->get_data());
	}

	// Public function: Allow/disallow internal links
	public function internal_link($internal_link) {
		$this->internal_link = $internal_link;
	}

	// Private function: Get data
	private function get_data() {
		$_data = array();

		// Add internal link conditionally
		if($this->internal_link === true) {
			$_data[] = array(
				'class' => 'icon-eye',
				'link' => WEB_ROOT.'/view/go/'.$this->go_term,
				'title' => 'View details of '.$this->go_term,
				'text' => 'View details of '.$this->go_term
				);
		}

		// Append external links
		foreach($this->external_links as $name => $url) {
			$_data[] = array(
				'link' => $this->generate_url($this->go_term, $url),
				'target' => '_blank',
				'title' => 'View '.$this->go_term.' on '.$name,
				'text' => $name
				);
		}

		return $_data;
	}

	// Private function: Generate URL
	private function generate_url($id, $template) {
		return str_replace('{{id}}', $id, $template);
	}

}

/* Component\DomainDropdown */
class DomainDropdown extends Dropdown {

	private $source;
	private $source_id;
	private $source_links = array(
		'CDD' => 'https://www.ncbi.nlm.nih.gov/Structure/cdd/cddsrv.cgi?uid={{id}}',
		'Gene3D' => 'http://www.cathdb.info/version/latest/superfamily/{{id}}',
		'Hamap' => 'http://hamap.expasy.org/profile/{{id}}',
		'PANTHER' => 'http://www.pantherdb.org/panther/family.do?clsAccession={{id}}',
		'Pfam' => 'http://pfam.xfam.org/family/{{id}}',
		'PIRSF' => 'http://pir.georgetown.edu/cgi-bin/ipcSF?id={{id}}',
		'PRINTS' => 'http://www.bioinf.manchester.ac.uk/cgi-bin/dbbrowser/sprint/searchprintss.cgi?prints_accn={{id}}&display_opts=Prints&category=None&queryform=false&regexpr=off',
		'ProDom' => 'http://prodom.prabi.fr/prodom/current/cgi-bin/request.pl?question=DBEN&query={{id}}',
		'ProSitePatterns' => 'http://prosite.expasy.org/cgi-bin/prosite/prosite-search-ac?{{id}}',
		'ProSiteProfiles' => 'http://prosite.expasy.org/cgi-bin/prosite/prosite-search-ac?{{id}}',
		'SFLD' => 'http://sfld.rbvi.ucsf.edu/django/family/{{id}}',
		'SMART' => 'http://smart.embl-heidelberg.de/smart/do_annotation.pl?BLAST=DUMMY&DOMAIN={{id}}',
		'SUPERFAMILY' => 'http://supfam.org/SUPERFAMILY/cgi-bin/scop.cgi?sunid={{id}}',
		'TIGRFAM' => 'http://jcvi.org/cgi-bin/tigrfams/HmmReportPage.cgi?acc={{id}}'
		);
	private $source_replace = array(
		'Gene3D' => array('G3DSA:', ''),
		'SFLD' => array('SFLDF', ''),
		'SUPERFAMILY' => array('SSF', '')
		);

	// Public function: Set source
	public function set_source($source, $source_id) {
		$this->source = $source;
		$this->source_id = $source_id;

		// Set title and data
		$this->set_title($source_id);
		$this->set_data($this->get_data());
	}

	// Private function: Get data
	private function get_data() {
		$_data = array(
			array(
				'link' => WEB_ROOT.'/view/domain/'.$this->source_id,
				'class' => 'icon-eye',
				'title' => 'View details on '.$this->source_id,
				'text' => 'View details on '.$this->source_id
				),
			array(
				'link' => WEB_ROOT.'/tools/trex?ids='.$this->source_id,
				'class' => 'icon-search',
				'title' => 'Search for proteins/transripts with this domain',
				'text' => 'Search for proteins/transripts with this domain'
				),
			);

		// Append external links
		if(array_key_exists($this->source, $this->source_links)) {
			if(array_key_exists($this->source, $this->source_replace)) {
				$_source_id = str_replace($this->source_replace[$this->source][0], $this->source_replace[$this->source][1], $this->source_id);
			} else {
				$_source_id = $this->source_id;
			}
			$url = str_replace('{{id}}', $_source_id, $this->source_links[$this->source]);
			$_data[] = array(
				'link' => $this->generate_url($_source_id, $this->source_links[$this->source]),
				'target' => '_blank',
				'title' => 'View external data on '.$this->source_id,
				'text' => 'View external data on '.$this->source_id
				);
		}

		return $_data;
	}

	// Private function: Generate URL
	private function generate_url($id, $template) {
		return str_replace('{{id}}', $id, $template);
	}

}

?>