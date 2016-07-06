<?php
$api->post('/corgi/{id}', function ($request, $response, $args) {

	try {

		$db = $this->get('db');

		// From https://stackoverflow.com/questions/32668186/slim-3-how-to-get-all-get-put-post-variables/
		$allPostPutVars = $request->getParsedBody();
		foreach($allPostPutVars as $key => $param){
			$p[$key] = escapeHTML($param);
		}

		// Overwrite ID with URL argument
		$p['ids'] = $args['id'];

		// Google Recaptcha
		$recaptcha = new \ReCaptcha\ReCaptcha(GRECAPTCHA_API_KEY);

		// Check if query ID type is defined
		if (isset($p['idtype']) && !empty($p['idtype'])) {
			$idtype = $p['idtype'];
		}

		// Check if ID is defined
		if (isset($p['ids']) && !empty($p['ids'])) {
			// Only allow one ID
			if(is_array($p['ids'])) {
				$query = $p['ids'][0];
			} else {
				$query = preg_split('/(,|;|\s+)/', $p['ids'])[0];
			}

			// If querying against gene ID database, we can discard isoform information
			if($idtype === 'geneid') {
				$query = preg_replace('/\.\d+$/', '', $p['ids']);
			} else {
				$query = $p['ids'];
			}

			// Check if IDs provided matches regex pattern
			if($idtype === 'transcriptid' && !preg_match('/^Lj([0-6]|chloro|mito|1_4_FUCT|XYLT)g\dv\d+\.(mrna)?\d+$/i', $query)) {
				throw new Exception('The query ID you have provided does not match the expected format. Example: <code>Lj4g3v0281040.1</code>', 400);
			}
			if($idtype === 'probeid' && !preg_match('/^(Ljwgs\_|LjU|Lj\_|chr[0-6]\.|gi|m[a-z]{2}|tc|tm|y4|rngr|cm).+\_at$/i', $query)) {
				throw new Exception('The query ID you have provided does not match the expected format. Example: <code>Lj4g3v0281040.1</code>', 400);
			}

		} else {
			throw new Exception('You have not specified a gene, probe, or transcript ID for your query.', 400);
		}

		// Check if dataset is defined
		if (isset($p['dataset']) && !empty($p['dataset'])) {
			$dataset = $p['dataset'];
		} else {
			throw new Exception('You have not selected a dataset to query against.', 400);
		}

		// Check if at least three columns are selected
		if (isset($p['column']) && count($p['column']) < 3) {
			throw new Exception('You must select at least three conditions to perform correlation analysis.', 400);
		}

		// Attempt to decode JWT. If user is not logged in, check Recaptcha
		$user = null;
		if(!empty($p['user_auth_token'])) {
			$user = auth_verify($p['user_auth_token']);
		}
		if(!$user && !isset($p['b'])) {
			// Check captcha
			if(empty($p['g-recaptcha-response'])) {
				throw new Exception('You have not provided a recaptcha token.', 400);
			}
			// Verify captcha
			else {
				$resp = $recaptcha->verify($p['g-recaptcha-response'], get_ip());
				if(!$resp->isSuccess()) {
					throw new Exception('You have provided an incorrect verification token.', 400);
				}
			} 
		}

		// Process incoming data
		$candidates_flat = NULL;
		if (isset($p['candidates']) && !empty($p['candidates'])) {
			$candidates_flat = implode(',', $p['candidates']);
		}

		$columns_flat = NULL;
		if (isset($p['column']) && !empty($p['column']) && is_array($p['column'])) {
			foreach ($p['column'] as $key => $col) {
				$columns[] = 'Mean_'.$col;
			}
			$columns_flat = implode(',', $columns);
		}

		// Cap number of genes returned
		$n = max(1, min($p['n'], 50));

		// Execute Python script
		$exec_str = PYTHON_PATH.' '.DOC_ROOT.'/lib/corx/CorrelatedGenes.py '.$dataset.' '.$query.' --top '.$n;
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
			return $response
				->withStatus(200)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 200,
					'data' => $result_json['data']
					)));
		} else {
			return $response
				->withStatus(500)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 500,
					'message' => 'We have encountered an issue with retrieving the correlation matrix.',
					'data' => $result_json
					)));
		}
	} catch(PDOException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'code' => $e->getCode(),
				'message' => $e->getMessage()
				)));

	} catch(Exception $e) {
		return $response
			->withStatus($e->getCode())
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => $e->getCode(),
				'message' => $e->getMessage()
				)));

	}
});
?>