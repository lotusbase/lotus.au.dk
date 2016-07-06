<?php

// Retrieve CORNEA data.
// Note: Since we are passing large amount of data, we are using POST
$api->post('/cornea/job/data/{id}', function ($request, $response, $args) {

	try {

		// From https://stackoverflow.com/questions/32668186/slim-3-how-to-get-all-get-put-post-variables/
		$allPostPutVars = $request->getParsedBody();
		foreach($allPostPutVars as $key => $param){
			$p[$key] = escapeHTML($param);
		}
		$p['job'] = $args['id'];


		$db = $this->get('db');

		// Resource type check
		if(isset($p['resourceType']) && in_array($p['resourceType'], array('file', 'svg', 'png', 'stream'))) {
			$resource_type = $p['resourceType'];
		} else {
			throw new Exception('Invalid resource type provided.', 400);
		}

		// Check if file data is provided
		if(isset($p['fileData']) && !empty($p['fileData'])) {
			$file_data = $p['fileData'];
		} else {
			throw new Exception('No file data has been specified.', 400);
		}

		

		// If we are using a filestream, perform a MIME type check
		if($resource_type === 'svg') {

			// Generate random job ID if none is found
			if(isset($p['job']) && !empty($p['job'])) {
				$job_hash_id = $p['job'];
			} else {
				$job_hash_id = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
			}
			
			$content = base64_decode($file_data);
			return $response
				->withHeader('Content-type', 'image/svg+xml')
				->withHeader('Content-length', strlen($content))
				->withHeader('Content-disposition', 'attachment; filename="cornea_'.$job_hash_id .'_'.date("Y-m-d_H-i-s").'.svg"')
				->withHeader('Cache-Control', 'no-cache, must-revalidate')
				->withHeader('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
				->write($content);

		} elseif($resource_type === 'png') {

			// Generate random job ID if none is found
			if(isset($p['job']) && !empty($p['job'])) {
				$job_hash_id = $p['job'];
			} else {
				$job_hash_id = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
			}

			// Adapted from http://stackoverflow.com/a/6417815/395910
			// Grab the MIME type and the data with a regex for convenience
			if (!preg_match('/data:([^;]*);base64,(.*)/', $file_data, $matches)) {
				throw new Exception ('Image provided is incorrectly coded with the wrong MIME type.');
			}

			// Decode image URL
			$content = base64_decode($matches[2]);
			return $response
				->withHeader('Content-type', $matches[1])
				->withHeader('Content-length', strlen($content))
				->withHeader('Content-disposition', 'attachment; filename="cornea_'.$job_hash_id .'_'.date("Y-m-d_H-i-s").'.png"')
				->withHeader('Cache-Control', 'no-cache, must-revalidate')
				->withHeader('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
				->write($content);

		} elseif($resource_type === 'file') {

			// Hash ID check
			if(isset($p['job']) && !empty($p['job'])) {
				$job_hash_id = $p['job'];
				if(!(preg_match('/((cli|standard)_)?[A-Fa-f0-9]{32}/', $job_hash_id))) {
					throw new Exception('Job ID provided is not a 32-character hexadecimal string.', 400);
				}
			} else {
				throw new Exception('Job is has not been specified.', 400);
			}

			// Update view and download count in database
			$q2 = $db->prepare('UPDATE correlationnetworkjob SET download_count = download_count + 1 WHERE hash_id = :hash_id LIMIT 1');
			$q2->bindParam(':hash_id', $job_hash_id);
			$q2->execute();

			// Check file path
			$file_path = DOC_ROOT.'/'.$file_data;
			if(file_exists($file_path)) {
				$content = file_get_contents($file_path);
				return $response
					->withHeader('Content-type', 'multipart/x-gzip')
					->withHeader('Content-length', strlen($content))
					->withHeader('Content-disposition', 'attachment; filename="cornea_'.$job_hash_id .'_'.date("Y-m-d_H-i-s").'.json.gz"')
					->withHeader('Cache-Control', 'no-cache, must-revalidate')
					->withHeader('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
					->write($content);
			} else {
				throw new Exception('File requested does not exist.', 404);
			}

		} elseif($resource_type === 'stream') {

			// Hash ID check
			if(isset($p['job']) && !empty($p['job'])) {
				$job_hash_id = $p['job'];
				if(!(preg_match('/((cli|standard)_)?[A-Fa-f0-9]{32}/', $job_hash_id))) {
					throw new Exception('Job ID provided is not a 32-character hexadecimal string.', 400);
				}
			} else {
				throw new Exception('Job is has not been specified.', 400);
			}

			// Update view count in database
			$q2 = $db->prepare('UPDATE correlationnetworkjob SET view_count = view_count + 1 WHERE hash_id = :hash_id LIMIT 1');
			$q2->bindParam(':hash_id', $job_hash_id);
			$q2->execute();

			// Check file path
			$file_path = DOC_ROOT.'/'.$file_data;
			if(file_exists($file_path)) {
				$content = gzopen($file_path, 'r');
				$stream = new Slim\Http\Stream($content);
				return $response
					->withBody($stream)
					->withHeader('Content-Type', 'application/json');
			} else {
				throw new Exception('File requested does not exist.', 404);
			}
		}

	} catch(PDOException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 400,
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

// Retrieve CORNEA data.
// Note: Since we are passing large amount of data, we are using POST
$api->get('/cornea/job/data/{id}', function ($request, $response, $args) {

	try {

		// From https://stackoverflow.com/questions/32668186/slim-3-how-to-get-all-get-put-post-variables/
		$job_hash_id = $args['id'];


		$db = $this->get('db');

		// Hash ID check
		if(!(preg_match('/((cli|standard)_)?[A-Fa-f0-9]{32}/', $job_hash_id))) {
			throw new Exception('Job ID provided is not a 32-character hexadecimal string.', 400);
		}

		// Update view and download count in database
		$q2 = $db->prepare('UPDATE correlationnetworkjob SET download_count = download_count + 1 WHERE hash_id = :hash_id LIMIT 1');
		$q2->bindParam(':hash_id', $job_hash_id);
		$q2->execute();

		// Check file path
		$file_path = DOC_ROOT.'/'.$file_data;
		if(file_exists($file_path)) {
			$content = file_get_contents($file_path);
			return $response
				->withHeader('Content-type', 'multipart/x-gzip')
				->withHeader('Content-length', strlen($content))
				->withHeader('Content-disposition', 'attachment; filename="cornea_'.$job_hash_id .'_'.date("Y-m-d_H-i-s").'.json.gz"')
				->withHeader('Cache-Control', 'no-cache, must-revalidate')
				->withHeader('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT')
				->write($content);
		} else {
			throw new Exception('File requested does not exist.', 404);
		}
	} catch(PDOException $e) {
		return $response
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 400,
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