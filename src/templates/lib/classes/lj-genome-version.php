<?php

namespace LotusBase;

/* LjGenomeVersion */
class LjGenomeVersion {

	private $version;
	public function __construct($d) {
		$this->version = $d['version'];
	}

	public function check() {
		// Sanity check for Lotus genome version
		if(!in_array(strval($this->version), $GLOBALS['lj_genome_versions'])) {
			return false;
		} else {
			return $this->version;
		}
	}
}
?>