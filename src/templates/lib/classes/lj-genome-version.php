<?php

namespace LotusBase;

/* LjGenomeVersion */
class LjGenomeVersion {

	private $genome;
	public function __construct($d = null) {
		$this->genome = $d['genome'];
	}

	public function check() {
		// Sanity check for Lotus genome version
		$genome_labels = $this->_getGenomeLabels();
		if(!in_array($this->genome, $genome_labels)) {
			return false;
		} else {
			return $this->genome;
		}
	}

	public function get_genome_ids() {
		return array_map(function($genome) {
			return implode('_', [$genome['ecotype'], $genome['version']]);
		}, $GLOBALS['lj_genome_versions']);
	}

	private function _getGenomeLabels() {
		return array_map(function($genome) {
			return implode('_', [$genome['ecotype'], $genome['version']]);
		}, $GLOBALS['lj_genome_versions']);
	}
}
?>