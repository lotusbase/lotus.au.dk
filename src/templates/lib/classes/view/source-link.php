<?php

namespace LotusBase\View;

/* View\SourceLink */
class SourceLink {

	private $_vars = array(
		'external_link' => array(
			'Gene3D' => 'http://www.cathdb.info/version/latest/superfamily/',
			'HMMPanther' => 'http://www.pantherdb.org/panther/family.do?clsAccession=',
			'HMMPfam' => 'http://pfam.xfam.org/family/',
			'Superfamily' => 'http://supfam.cs.bris.ac.uk/SUPERFAMILY/cgi-bin/scop.cgi?ipid=',
			'PatternScan' => 'http://prosite.expasy.org/',
			'ProfileScan' => 'http://prosite.expasy.org/'
			),
		'external_id_pattern' => array(
			'Gene3D' => '/^G3DSA:(.*)$/'
			),
		'domain' => array(
			'HMMPfam' => 'pfam'
			)
		);

	// Public function: Set source
	public function set_source($source) {
		$this->_vars['Source'] = $source;
	}

	// Public function: Set ID
	public function set_id($id) {
		$this->_vars['SourceID'] = $id;
	}

	// Private function: Get external link
	private function get_external_url() {
		// Variables
		$source = $this->_vars['Source'];
		$sourceID = $this->_vars['SourceID'];

		// Set up replacement
		$pattern = $this->_vars['external_id_pattern'][$source];
		if(!empty($pattern)) {
			$url = $this->_vars['external_link'][$source] . preg_replace($pattern, '$1', $sourceID);
		} else {
			$url = $this->_vars['external_link'][$source] . $sourceID;
		}

		return $url;
	}

	// Public function: Get HTML
	public function get_html() {

		// Generate output
		$out = '<div class="dropdown button">';
		$out .= '<span class="dropdown--title">'.$this->_vars['SourceID'].'</span>';
		$out .= '<ul>';
		if(array_key_exists($this->_vars['Source'], $this->_vars['domain'])) {
			$out .= '<li><a href="'.WEB_ROOT.'/api/v1/view/domain/'.$this->_vars['domain'][$this->_vars['Source']].'/'.$this->_vars['SourceID'].'" data-desc-id="'.$this->_vars['SourceID'].'" data-desc-source="'.$this->_vars['domain'][$this->_vars['Source']].'"><span class="icon-eye">Show description</span></a></li>';
		}
		if(array_key_exists($this->_vars['Source'], $this->_vars['external_link'])) {
			$out .= '<li><a href="'.$this->get_external_url().'"><span class="icon-link-ext">View external data</span></a></li>';
		}
		$out .= '<li><a href="'.WEB_ROOT.'/tools/trex/?ids='.$this->_vars['SourceID'].'" title="Search for proteins/transripts with this domain"><span class="icon-search">Search for proteins/transripts with this domain</span></a></li>';
		$out .= '</ul>';
		$out .= '</div>';

		// Return output
		return $out;
	}

}

?>