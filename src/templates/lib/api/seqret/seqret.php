<?php
// Initialize error array
$input_err = array();

// Is database and ID defined?
if(!empty($_POST['db']) && !empty($_POST['id'])) {
	$db = $_POST['db'];
	$id = $_POST['id'];
	$out = '';
	if(isset($_POST['type'])) {
		$type = $_POST['type'];
	} else {
		$type = 1;
	}
	
	// Format input for IDs so they're separated by commas
	$id_pattern = array(
		'/ *[\r\n]+/',		// Checks for one or more line breaks
		'/(\w)\s+(\w)/',	// Checks for words separated by one or more spaces
		'/,\s*/',			// Checks for words separated by comma, but with variable spaces
		'/,\s*,/'			// Checks for empty strings (i.e. no words between two commas)
		);
	$id_replace = array(
		',',
		'$1, $2',
		',',
		','
		);

	// Process ID accordingly
	if(!is_array($id)) {
		$id = preg_replace($id_pattern, $id_replace, $id);
	}

	// Coerce strand
	if(!empty($_POST['st'])) {
		$st = $_POST['st'];
	} else {
		$st = "plus";
	}

	// Coerce position
	if(isset($_POST['from']) && isset($_POST['to'])) {
		$pre_from = intval($_POST['from']);
		$pre_to = intval($_POST['to']);

		// Sanity check for from and to positions if they have been specified by user
		if($pre_from > $pre_to) {
			// If from and to positions are switched, get minus strand ONLY if auto option is on
			$from = $pre_to;
			$to = $pre_from;
			if($st == 'auto') {
				$st = "minus";
			}
		} elseif($pre_from == $pre_to && $pre_from !== 0) {
			// If from and to positions are identical
			$input_err = 'Your start and end positions are identical.';
		} else {
			// If from and to positions are in the correct order, get plus strand ONLY if auto option is on
			$from = $pre_from;
			$to = $pre_to;
			if($st == 'auto') {
				$st = "plus";
			}
		}
	} else {
		// If from and to positions are not specified
		$from = 0;
		$to = 0;
		if($st == 'auto') {
			$st = "plus";
		}
	}

	// Define BLAST db location
	if(is_intranet_client()) {
		$db_directory = '/var/blast-carb/db';
	} else {
		$db_directory = '/var/blast/db';
	}

} elseif(empty($_POST['db'])) {
	$input_err[] = 'An invalid/non-existent database was selected for querying.';
} elseif(empty($_POST['id'])) {
	$input_err[] = 'No accession or ID has been provided.';
}

// Error catch-all
if(count($input_err) > 0) {
	// If an error flag is raised
	$error->set_message($input_err);
	$error->execute();
}

// Now we move on
exec('perl /var/www/html/lib/blast-seqret.cgi'.' '.escapeshellarg($db_directory).' '.escapeshellarg($db).' '.escapeshellarg($id).' '.escapeshellarg($from).' '.escapeshellarg($to).' '.escapeshellarg($st), $output);

// If there is an error with the script output
if(empty($output)) {
	$error->set_message('The script has failed to execute. Please contact system administrator.');
	$error->execute();
	
} else {
	if($output[0] == 'ERR') {
		$error->set_message($output[1]);
		$error->execute();
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

		// When to force download
		if(isset($_POST['download']) && intval($_POST['download']) === 1) {
			$file = "seqret_output_db-".preg_replace('/\.fa/i', '', $db)."_" . date("Y-m-d_H-i-s") . ".fa";
			header("Content-Type: application/octet-stream");
			header("Content-disposition: attachment; filename=\"".$file."\"");
			header("Cache-Control: no-cache, must-revalidate");
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
			foreach ($fasta_items as $key => $fasta_item) {
				echo '>'.$fasta_item['header']."\n".$fasta_item['sequence']."\n\n";
			}
		} else {
			$dataReturn->set_data(array(
				'success' => true,
				'fasta' => $fasta_items,
				'forceDownload' => (count($fasta_items) > 50 || abs($to - $from) > 10000) ? true : false,
				'forceDownloadMessage' => (count($fasta_items) > 50 || abs($to - $from) > 10000) ? 'large number of rows were   returned' : 'an excessively long sequence was returned'
			));
			$dataReturn->execute();
		}
	}
}
?>