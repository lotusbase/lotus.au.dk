<?php

namespace LotusBase\CORx\CORNEA;
use \PDO;

/* CORx\CORNEA\Download */
class Download {

	private $_v = array();

	// Construct
	public function __construct($v) {

		// Set values
		$this->_v = $v;
	}

	// PUBLIC
	// Get payload
	public function get_payload() {
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		// Resource type check
		if(isset($this->_v['resourceType']) && in_array($this->_v['resourceType'], array('file', 'svg', 'png', 'stream'))) {
			$resource_type = $this->_v['resourceType'];
		} else {
			throw new Exception('Invalid resource type provided.');
		}

		// Check if file data is provided
		if(isset($this->_v['fileData']) && !empty($this->_v['fileData'])) {
			$file_data = $this->_v['fileData'];
		} else {
			throw new Exception('No file data has been specified.');
		}

		// Hash ID check
		if(isset($this->_v['job'])) {
			$job_hash_id = $this->_v['job'];
			if(!(preg_match('/((cli|standard)_)?[A-Fa-f0-9]{32}/', $job_hash_id))) {
				throw new Exception('Job ID provided is not a 32-character hexadecimal string.');
			}
		} else {
			throw new Exception('Job is has not been specified.');
		}

		// If we are using a filestream, perform a MIME type check
		if($resource_type === 'svg') {
			
			$content = base64_decode($file_data);
			header('Content-type: image/svg+xml');
			header('Content-length: '.strlen($content));
			header('Content-disposition: attachment; filename="cornea_'.$job_hash_id .'_'.date("Y-m-d_H-i-s").'.svg"');
			header('Cache-Control: no-cache, must-revalidate');
			header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
			echo $content;
			exit();

		} elseif($resource_type === 'png') {

			// Adapted from http://stackoverflow.com/a/6417815/395910
			// Grab the MIME type and the data with a regex for convenience
			if (!preg_match('/data:([^;]*);base64,(.*)/', $file_data, $matches)) {
				throw new Exception ('Image provided is incorrectly coded with the wrong MIME type.');
			}

			// Decode image URL
			$content = base64_decode($matches[2]);
			header('Content-type: '.$matches[1]);
			header('Content-length: '.strlen($content));
			header('Content-disposition: attachment; filename="cornea_'.$job_hash_id .'_'.date("Y-m-d_H-i-s").'.png"');
			header('Cache-Control: no-cache, must-revalidate');
			header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
			echo $content;
			exit();

		} elseif($resource_type === 'file') {

			// Update download count in database
			try {
				// Update view count
				$q2 = $db->prepare('UPDATE correlationnetworkjob SET download_count = download_count + 1 WHERE hash_id = :hash_id LIMIT 1');
				$q2->bindParam(':hash_id', $job_hash_id);
				$q2->execute();
			} catch(PDOException $e) {
				$error->set_message('We have encountered an error: '.$e->getMessage());
				$error->execute();
			}

			// Check file path
			$file_path = DOC_ROOT.'/'.$file_data;
			if(file_exists($file_path)) {
				
				$content = file_get_contents($file_path);
				header('Content-type: multipart/x-gzip');
				header('Content-length: '.strlen($content));
				header('Content-disposition: attachment; filename="cornea_'.$job_hash_id .'_'.date("Y-m-d_H-i-s").'.json.gz"');
				header('Cache-Control: no-cache, must-revalidate');
				header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
				echo $content;
				exit();

			} else {
				throw new Exception('File requested does not exist.');
			}
		} elseif($resource_type === 'stream') {

			// Update view count in database
			try {
				// Update view count
				$q2 = $db->prepare('UPDATE correlationnetworkjob SET view_count = view_count + 1 WHERE hash_id = :hash_id LIMIT 1');
				$q2->bindParam(':hash_id', $job_hash_id);
				$q2->execute();
			} catch(PDOException $e) {
				$error->set_message('We have encountered an error: '.$e->getMessage());
				$error->execute();
			}

			// Check file path
			$file_path = DOC_ROOT.'/'.$file_data;
			if(file_exists($file_path)) {
				$content = gzopen($file_path, 'r');
				while ($chunk = gzread($content, 65536)) {
					echo $chunk;
				}
			} else {
				throw new Exception('File requested does not exist.');
			}
		}

		return $this->_v;
	}
}
?>