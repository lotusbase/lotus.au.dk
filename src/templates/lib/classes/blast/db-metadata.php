<?php

namespace LotusBase\BLAST;
use \PDO;

/* BLAST\DBMetadata */
class DBMetadata {

	// Database metadata
	private $blast_db_metadata = array();
	private $blast_db_metadata_extra = array();

	// Constructor
	function __construct() {
		try {
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$q = $db->prepare('SELECT
				`Name`,
				`Description`,
				`Category`,
				`Type`,
				`HasDropdownGI`
				FROM blast_db
				ORDER BY IDKey DESC
			');
			$q->execute();
			if($q->rowCount()) {
				while($row = $q->fetch(PDO::FETCH_ASSOC)) {
					$this->blast_db_metadata_extra[$row['Name']] = array(
						'description' => $row['Description'],
						'category' => $row['Category'],
						'type' => $row['Type'],
						'gi_dropdown' => $row['HasDropdownGI']
					);
				}
			}
	
		} catch(\PDOException $e) {
			throw new \Exception('Unable to fetch BLAST database metadata.');
		}
	}

	// Vars
	private $_vars = array(
		'blacklist' => array(),
		'whitelist' => array()
		);

	// Filter database by blacklist
	public function set_db_blacklist($filter) {
		$this->_vars['blacklist'] = array_merge($this->_vars['blacklist'], $filter);
	}

	// Filter database by whitelist
	public function set_db_whitelist($filter) {
		$this->_vars['whitelist'] = array_merge($this->_vars['whitelist'], $filter);
	}

	// Get blast directory
	private function get_blast_db_dir() {
		if(is_allowed_access('/blast/')) {
			return BLAST_DB_DIR_INTERNAL;
		} else {
			return BLAST_DB_DIR_PUBLIC;
		}
	}

	// Remove database
	private function remove_database($var) {
		$status = true;

		// Check if file path is specified
		if(!isset($var['abs_file_path'])) {
			$status = false;
		}

		// Check if database is blacklisted
		foreach ($this->_vars['blacklist'] as $key => $values) {
			if (!in_array($var[$key], $values)) {
				$status = true;
			} else {
				$status = false;
				break;
			}
		}

		// Check if database is whitelisted
		foreach ($this->_vars['whitelist'] as $key => $values) {
			if (in_array($var[$key], $values)) {
				$status = true;
				break;
			} else {
				$status = false;
			}
		}

		return $status;
	}

	// Get metadata
	public function get_metadata($db_file_name = null) {
		exec('perl '.DOC_ROOT.'/lib/blast-db-metadata.cgi'.' '.escapeshellarg($this->get_blast_db_dir()), $output);
		foreach($output as $db) {
			$db_raw_data = preg_split('/\t/', $db);
			$db_keys = array(
				'abs_file_path',
				'molecular_type',
				'title',
				'last_updated',
				'base_count',
				'sequence_count',
				'bytes_used'
			);
			foreach($db_raw_data as $i => $d) {
				$this->blast_db_metadata[basename($db_raw_data[0])][$db_keys[$i]] = $d;
			}
		}

		// Merge additional metadata
		$this->blast_db_metadata = array_merge_recursive($this->blast_db_metadata_extra, $this->blast_db_metadata);

		// Are we retrieving all databases, or a single DB?
		if(!isset($db_file_name) || !trim($db_file_name)) {
			$this->blast_db_metadata = array_filter($this->blast_db_metadata, array(__CLASS__, "remove_database"));
		} else {
			$this->blast_db_metadata = array_filter($this->blast_db_metadata, array(__CLASS__, "remove_database"))[$db_file_name];
		}

		return $this->blast_db_metadata;

	}

}

?>