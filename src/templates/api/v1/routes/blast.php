<?php

$api->get('/blast', function($request, $response, $args) {
	return $response
		->withStatus(200)
		->withHeader('Content-Type', 'application/json')
		->write(json_encode(array(
			'status' => 200,
			'message' => 'Welcome to the BLAST API.'
			)));
});

// Sequence Retrieval
$api->get('/blast/{db}/{id}', function($request, $response, $args) {

	try {
		// Get ID and database
		$id = $args['id'];
		$db = $args['db'];

		// Retrieve GET variables
		$g			= $request->getQueryParams();
		$download	= isset($g['download']) ? $g['download'] : null;
		$strand		= isset($g['strand']) ? $g['strand'] : null;
		$from		= isset($g['from']) ? $g['from'] : null;
		$to			= isset($g['to']) ? $g['to'] : null;

		$dbs = new \LotusBase\BLAST\DBMetadata();
		$db_metadata = $dbs->get_metadata($db);

		// Coerce strand
		if(!in_array($strand, array('plus', 'minus'))) {
			$st = 'plus';
		} else {
			$st = $strand;
		}

		// Coerce position
		if(isset($from) || isset($to)) {
			$pre_from = intval($from);
			$pre_to = intval($to);

			// Sanity check for from and to positions if they have been specified by user
			if($pre_from > $pre_to) {
				// If from and to positions are switched, get minus strand ONLY if auto option is on
				$from = $pre_to;
				$to = $pre_from;
				if($st == 'auto') {
					$st = 'minus';
				}
			} elseif($pre_from === $pre_to && $pre_from !== 0) {
				// If from and to positions are identical
				throw new Exception('Your start and end positions are identical.');
			} else {
				// If from and to positions are in the correct order, get plus strand ONLY if auto option is on
				$from = $pre_from;
				$to = $pre_to;
				if($st == 'auto') {
					$st = 'plus';
				}
			}
		} else {
			// If from and to positions are not specified
			$from = 0;
			$to = 0;
			if($st === 'auto') {
				$st = 'plus';
			}
		}

		// Cap position if user is querying genome and it is too big
		if(
			(isset($db_metadata['type']) && $db_metadata['type'] === 'genome') &&
			!isset($download) &&
			(
				($from === 0 && $to === 0) ||
				abs($from - $to) > 100000
				)
			) {

			return $response
				->withStatus(400)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 400,
					'message' => 'Query size expected to be too long. Append your query with `?download` to trigger forced download.'
					)));
		}

		// Define BLAST db location
		if(is_allowed_access('/blast/')) {
			$db_directory = '/var/blast-carb/db';
		} else {
			$db_directory = '/var/blast/db';
		}

		// Construct BLAST exec query
		$blast_exec = '/usr/bin/perl '.DOC_ROOT.'/lib/blast-seqret.cgi'.' '.escapeshellarg($db_directory).' '.escapeshellarg($db).' '.escapeshellarg($id).' '.escapeshellarg($from).' '.escapeshellarg($to).' '.escapeshellarg($st);

		if(isset($download)) {

			// Create temporary file so that large output can be streamed
			$temp_file = tempnam(sys_get_temp_dir(), "blast_");
			exec($blast_exec.' > '.escapeshellarg($temp_file));

			if(file_exists($temp_file)) {
				$content = fopen($temp_file, 'r');
				$output = new Slim\Http\Stream($content);
				$file = "seqret_output_db-".preg_replace('/\.fa/i', '', $db)."_" . date("Y-m-d_H-i-s") . ".fa";
				return $response
					->withBody($output)
					->withHeader('Content-Type', 'application/octet-stream')
					->withHeader('Content-disposition', 'attachment; filename="'.$file.'"')
					->withHeader('Cache-Control', 'no-cache, must-revalidate')
					->withHeader('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT');

				unlink($temp_file);

			} else {
				throw new Exception('File requested does not exist.', 404);
			}

		} else {

			// Pass output directly to $output
			exec($blast_exec, $output);

			// If there is an error with the script output
			if(empty($output)) {
				return $response
					->withStatus(500)
					->withHeader('Content-Type', 'application/json')
					->write(json_encode(array(
						'status' => 500,
						'message' => 'The script has failed to execute. Please contact system administrator.'
						)));
			} else {
				if($output[0] === 'ERR') {
					throw new Exception($output[1]);
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

					// Return BLAST
					return $response
						->withStatus(200)
						->withHeader('Content-Type', 'application/json')
						->write(json_encode(array(
							'status' => 200,
							'data' => array(
								'success' => true,
								'fasta' => $fasta_items
								)
							)));
				}
			}
		}

	} catch(Exception $e) {
		return $response
			->withStatus($e->getCode())
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => $e->getCode(),
				'data' => $e->getMessage()
				)));
	}


});

?>