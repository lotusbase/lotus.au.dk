<?php

namespace LotusBase\BLAST;

/* BLAST\Query */
class Query {

	// Vars
	private $_vars = array(
		'strand' => 'auto',					# Default: Use intelligent strand detection
		'from' => 0,						# Default: Use position 0 (will return whole sequence)
		'to' => 0,							# Default: Use position 0 (will return whole sequence)
		'db_directory' => '/var/blast/db'	# Default: Use the public BLAST database directory
		);

	// Set ID
	public function set_id($id) {
		$this->_vars['id'] = $id;
	}

	// Set database
	public function set_database($database) {
		$this->_vars['database'] = $database;
	}

	// Set strand
	public function set_strand($strand) {
		// Coerce strand
		if(in_array($strand, array('plus', 'minus', 'auto'))) {
			$this->_vars['strand'] = $strand;
		}
	}

	// Set position
	public function set_position($from, $to) {
		// Coerce position
		if(!empty($from) || !empty($to)) {
			$_from = intval($from);
			$_to = intval($to);

			// Sanity check for from and to positions if they have been specified by user
			if($_from > $_to) {

				// If from and to positions are switched, get minus strand ONLY if auto option is on
				$this->_vars['from']	= $_to;
				$this->_vars['to']		= $_from;
				$this->_vars['strand']	= ($this->_vars['strand'] === 'auto' ? 'minus' : $this_vars['strand']);

			} elseif($_from === $_to && $_from !== 0) {

				// If from and to positions are identical
				throw new \Exception('Your start and end positions are identical.');

			} else {

				// If from and to positions are in the correct order, get plus strand ONLY if auto option is on
				$this->_vars['from']	= $_from;
				$this->_vars['to']		= $_to;
				$this->_vars['strand']	= ($this->_vars['strand'] === 'auto' ? 'plus' : $this_vars['strand']);

			}
		} else {

			// If from and to positions are not specified
			$this->_vars['from'] = 0;
			$this->_vars['to'] = 0;
			$this->_vars['strand']	= ($this->_vars['strand'] === 'auto' ? 'plus' : $this_vars['strand']);
		}
	}

	// Get query by execution
	public function execute($download = false) {

		// Define alternative BLAST db location if user has access to path
		if(is_allowed_access_by_path('/blast/')) {
			$this->_vars['db_directory'] = '/var/blast-carb/db';
		}

		// Construct BLAST exec query
		$blast_exec = '/usr/bin/perl '.DOC_ROOT.'/lib/blast-seqret.cgi'.' '.escapeshellarg($this->_vars['db_directory']).' '.escapeshellarg($this->_vars['database']).' '.escapeshellarg($this->_vars['id']).' '.escapeshellarg($this->_vars['from']).' '.escapeshellarg($this->_vars['to']).' '.escapeshellarg($this->_vars['strand']);

		// Pass output directly to $output
		exec($blast_exec, $output);

		// If there is an error with the script output
		if(empty($output)) {
			throw new \Exception('The script has failed to execute. Please contact system administrator.');
		} else {
			if($output[0] === 'ERR') {
				throw new \Exception($output[1]);
			} else {
				// Process data
				// Construct array of headers, ids, and array of sequences
				foreach($output as $line) {
					if(strlen($line) > 0 && $line[0] === '>') {
						$fasta_seqs[] = '';
						$fasta_ids[] = preg_replace('/>lcl\|([0-9a-z\._]+).*(\s.*)?/i', '$1', $line);
						$fasta_headers[] = substr($line, 1);
					} else {
						$fasta_seqs[count($fasta_ids)-1] = $fasta_seqs[count($fasta_ids)-1].$line;
					}
				}

				// Combine headers and sequences one-on-one
				foreach($fasta_ids as $key=>$fasta_id) {
					$fasta_items[] = array(
						'id' => $fasta_id,
						'header' => $fasta_headers[$key],
						'sequence' => $fasta_seqs[$key]
					);
				}

				// Return BLAST results
				return $fasta_items;
			}
		}
	}

}

?>