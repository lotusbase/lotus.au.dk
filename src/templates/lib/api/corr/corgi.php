<?php

// Google Recaptcha
$recaptcha = new \ReCaptcha\ReCaptcha(GRECAPTCHA_API_KEY);

// Check if query ID type is defined
if (isset($_POST['idtype']) && !empty($_POST['idtype'])) {
	$idtype = $_POST['idtype'];
}

// Check if ID is defined
if (isset($_POST['ids']) && !empty($_POST['ids'])) {
	// Only allow one ID
	if(is_array($_POST['ids'])) {
		$query = $_POST['ids'][0];
	} else {
		$query = preg_split('/(,|;|\s+)/', $_POST['ids'])[0];
	}

	// If querying against gene ID database, we can discard isoform information
	if($idtype === 'geneid') {
		$query = preg_replace('/\.\d+$/', '', $_POST['ids']);
	} else {
		$query = $_POST['ids'];
	}

	// Check if IDs provided matches regex pattern
	if($idtype === 'transcriptid' && !preg_match('/^Lj([0-6]|chloro|mito|1_4_FUCT|XYLT)g\dv\d+\.(mrna)?\d+$/i', $query)) {
		$error->set_message('The query ID you have provided does not match the expected format. Example: <code>Lj4g3v0281040.1</code>');
		$error->execute();
	}
	if($idtype === 'probeid' && !preg_match('/^(Ljwgs\_|LjU|Lj\_|chr[0-6]\.|gi|m[a-z]{2}|tc|tm|y4|rngr|cm).+\_at$/i', $query)) {
		$error->set_message('The query ID you have provided does not match the expected format. Example: <code>Lj4g3v0281040.1</code>');
		$error->execute();
	}

} else {
	$error->set_message('You have not specified a gene, probe, or transcript ID for your query.');
	$error->execute();
}

// Check if dataset is defined
if (isset($_POST['dataset']) && !empty($_POST['dataset'])) {
	$dataset = $_POST['dataset'];
} else {
	$error->set_message('You have not selected a dataset to query against.');
	$error->execute();
}

// Check if at least three columns are selected
if (isset($_POST['column']) && count($_POST['column']) < 3) {
	$error->set_message('You must select at least three conditions to perform correlation analysis.');
	$error->execute();
}

// Check if Google recaptcha passes
if(!verify_session_key(array(
	'userid' => $_POST['user_id'],
	'sessionkey' => $_POST['session_key']
	))) {
	// Check captcha
	if(empty($_POST['g-recaptcha-response'])) {
		$error->set_message('You have not provided a recaptcha token.');
		$error->execute();
	}
	// Verify captcha
	else {
		$resp = $recaptcha->verify($_POST['g-recaptcha-response'], get_ip());
		if(!$resp->isSuccess()) {
			$error->set_message('You have provided an incorrect verification token.');
			$error->execute();
		}
	} 
}

// Process incoming data
$candidates_flat = NULL;
if (isset($_POST['candidates']) && !empty($_POST['candidates'])) {
	$candidates_flat = implode(',', $_POST['candidates']);
}

$columns_flat = NULL;
if (isset($_POST['column']) && !empty($_POST['column']) && is_array($_POST['column'])) {
	foreach ($_POST['column'] as $key => $col) {
		$columns[] = 'Mean_'.$col;
	}
	$columns_flat = implode(',', $columns);
}

// Cap number of genes returned
$n = max(1, min($_POST['n'], 50));

// Execute Python script
$exec_str = PYTHON_PATH.' /var/www/html'.WEB_ROOT.'/lib/python/corr/CorrelatedGenes.py '.$dataset.' '.$query.' --top '.$n;
if ($candidates_flat !== NULL) {
	$exec_str = $exec_str.' --candidates '.$candidates_flat;
}
if ($columns_flat !== NULL) {
	$exec_str = $exec_str.' --columns '.$columns_flat;
}
// 2>$1 redirects stderr to stdout so we can see the errors on screen.
$result = exec($exec_str);
$result_json = json_decode($result, true);

// Parse result
if(array_key_exists('success', $result_json)) {

	// Fetch gene annotations
	try {
		foreach ($result_json['data'] as $key => $point) {
			// Prepare query
			$q = $db->prepare("SELECT Gene, Annotation, LjAnnotation, ID FROM annotations30 WHERE Gene LIKE ? LIMIT 1");

			// Bind params and execute query
			$q->execute(array($point['id'].'%'));

			if($q->rowCount() === 1) {
				$row = $q->fetch(PDO::FETCH_ASSOC);
				$result_json['data'][$key]['Annotation'] = $row['Annotation'];
				$result_json['data'][$key]['LjAnnotation'] = $row['LjAnnotation'];
			}
		}
		
		// Return data
		$dataReturn->set_data($result_json['data']);
		$dataReturn->execute();
	} catch(PDOException $e) {
		$error->set_message('We have encountered an error: '.$e->getMessage());
		$error->execute();
	}
} else {
	$error->set_message('We have encountered an issue with retrieving the correlation matrix.');
	$error->set_data($result_json);
	$error->execute();
}
?>