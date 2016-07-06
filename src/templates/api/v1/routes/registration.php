<?php
	// Use JWT
	use \Firebase\JWT\JWT;

	// Pre-registration check
	$api->get('/registration', function ($request, $response) {
		try {

			$g = $request->getQueryParams();
			$db = $this->get('db');

			// Check if data is complete
			if(!isset($g['field']) || !isset($g['value'])) {
				throw new Exception('Missing field and/or value.', 400);
			}

			// Whitelist of fields
			$field_list = array(
				'username' => 'Username',
				'email' => 'Email'
				);

			if(!array_key_exists($g['field'], $field_list)) {
				throw new Exception('Invalid field.', 400);
			}

			$q = $db->prepare('SELECT COUNT(*) AS C FROM auth WHERE '.$g['field'].' = ?'.(isset($g['ignoreCurrent']) ? ' AND '.$g['field'].' != ?' : ''));
			$vals = array($g['value']);
			if(isset($g['ignoreCurrent'])) {
				// Attempt to decrypt token
				$jwt_decoded = json_decode(json_encode(JWT::decode(preg_replace('/^Bearer\s?(.*)$/', '$1', $request->getHeaders()['HTTP_AUTHORIZATION'][0]), JWT_SECRET, array('HS256'))), true);
				$userData = $jwt_decoded['data'];
				if(!isset($userData[$field_list[$g['field']]])) {
					throw new Exception('User identifier not found.', 400);
				} else {
					$vals[] = $userData[$field_list[$g['field']]];
				}
			}
			$e = $q->execute($vals);

			if(!$e) {
				throw new Exception('Failed to perform database query.', 500);
			} elseif($q->rowCount()) {
				$r = $q->fetch(PDO::FETCH_ASSOC);
				if($r['C'] > 0) {
					return $response
						->withStatus(200)
						->withHeader('Content-Type', 'text/html')
						->write('false');
				}
			}

			return $response
				->withStatus(200)
				->withHeader('Content-Type', 'text/html')
				->write('true');

		} catch(PDOException $e) {
			return $response
				->withStatus(500)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 500,
					'message' => $e->getMessage(),
					'code' => $e->getCode()
					)));
		} catch(Firebase\JWT\SignatureInvalidException $e) {
			return $response
				->withStatus(500)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => 500,
					'message' => $e->getMessage(),
					'code' => $e->getCode()
					)));
		} catch(Exception $e) {
			return $response
				->withStatus(500)
				->withHeader('Content-Type', 'application/json')
				->write(json_encode(array(
					'status' => $e->getCode(),
					'message' => $e->getMessage()
					)));
		}
	});
?>