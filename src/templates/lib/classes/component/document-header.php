<?php

namespace LotusBase\Component;

/* Component\DocumentHeader */
class DocumentHeader {

	private $header = array();

	// Construct
	public function __construct() {

		// Default canonical URL
		$this->header['canonical_url'] = 'https://lotus.au.dk'.$_SERVER['REQUEST_URI'];

		// Default header meta tags
		$this->header['meta'] = array(
			'author' => 'Lotus Base',
			'description' => 'Lotus Base: a collection of genomics and proteomics resources for the model legume Lotus japonicus, from Aarhus University, Denmark'
			);

	}

	// Set canonical URL
	public function set_canonical_url($canonical_url) {
		if ($canonical_url === null) {
			$this->header['canonical_url'] = null;
		} else if(strpos($canonical_url, 'https://lotus.au.dk') >= 0) {
			$this->header['canonical_url'] = $canonical_url;
		} else {
			$this->header['canonical_url'] = 'https://lotus.au.dk'.$canonical_url;
		}
	}

	// Set meta tags
	public function set_meta_tags($meta_array) {
		$this->header['meta'] = array_replace_recursive($this->header['meta'], $meta_array);
	}

	// Return meta tags
	private function get_meta_tags() {
		foreach ($this->header['meta'] as $meta_name => $meta_content) {
			$meta[] = '<meta name="'.$meta_name.'" content="'.$meta_content.'" />';
		}
		return implode("\n", $meta);
	}

	// Return document header
	public function get_document_header() {
		return '
			<meta charset="utf-8" />
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1.0" />

			<!-- Stylesheets -->
			<link rel="stylesheet" href="'.WEB_ROOT.'/dist/css/ggs.min.css" type="text/css" media="screen" />
			<link rel="stylesheet" href="'.WEB_ROOT.'/dist/css/styles.min.css" type="text/css" media="screen" />
			<link rel="stylesheet" href="'.WEB_ROOT.'/lib/header-icon-css" type="text/css" media="screen" />
			<link rel="stylesheet" href="'.WEB_ROOT.'/dist/css/print.min.css" type="text/css" media="print" />
			<link rel="shortcut icon" type="image/png" href="'.WEB_ROOT.'/dist/images/branding/favicon-32x32.png" />

			<!-- Google Fonts -->
			<link href="https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,300,400,600|Open+Sans+Condensed:300|Lora:400,400italic,700,700italic" rel="stylesheet prefetch" type="text/css">

			<script src='.WEB_ROOT.'"/dist/js/plugins/modernizr.min.js"></script>

			<!-- Metadata -->
			'.$this->get_meta_tags().'

			<!-- Canonical URL -->
			'.($this->header['canonical_url'] !== null ? '<link rel="canonical" href="'.$this->header['canonical_url'].'" itemprop="url" />' : '').'

			<!--[if IE]>
			<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
			<![endif]-->
		';
	}
}
?>