<?php
// Escape HTML
function escapeHTML($str) {
	$_str = trim($str);
	return htmlspecialchars($_str, ENT_QUOTES, 'UTF-8');
}

// Pluralize
function pl($count, $singular, $plural = false) {
	if (!$plural) $plural = $singular . 's';
	return ($count == 1 ? $singular : $plural);
}

// Format numbers
function nf($str) {
	return number_format($str);
}

// Format nucleic acid
function naseq($str) {
	if(!empty($str)) {
		return chunk_split($str, 80, ' ');
	} else {
		return $str;
	}
}

// Multidimensional array sorter
function array_sorter() {
	// Normalize criteria up front so that the comparer finds everything tidy
	$criteria = func_get_args();
	foreach ($criteria as $index => $criterion) {
		$criteria[$index] = is_array($criterion)
			? array_pad($criterion, 3, null)
			: array($criterion, SORT_ASC, null);
	}

	return function($first, $second) use (&$criteria) {
		foreach ($criteria as $criterion) {
			// How will we compare this round?
			list($column, $sortOrder, $projection) = $criterion;
			$sortOrder = $sortOrder === SORT_DESC ? -1 : 1;

			// If a projection was defined project the values now
			if ($projection) {
				$lhs = call_user_func($projection, $first[$column]);
				$rhs = call_user_func($projection, $second[$column]);
			}
			else {
				$lhs = $first[$column];
				$rhs = $second[$column];
			}

			// Do the actual comparison; do not return if equal
			if ($lhs < $rhs) {
				return -1 * $sortOrder;
			}
			else if ($lhs > $rhs) {
				return 1 * $sortOrder;
			}
		}

		return 0; // tiebreakers exhausted, so $first == $second
	};
}

function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
	if(count($arr) > 2) {
		$sort_col = array();
		foreach ($arr as $key => $row) {
			$sort_col[$key] = $row[$col];
		}

		array_multisort($sort_col, $dir, $arr);
	}
}

// Flatten array
function array_flatten($arg) {
  return is_array($arg) ? array_reduce($arg, function ($c, $a) { return array_merge($c, array_flatten($a)); },[]) : [$arg];
}

// Get client IP
function get_ip() {
	$headers = $_SERVER;

	//Get the forwarded IP if it exists
	if ( array_key_exists( 'X-Forwarded-For', $headers ) && filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		$the_ip = $headers['X-Forwarded-For'];
	} elseif ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ) && filter_var( $headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 )
	) {
		$the_ip = $headers['HTTP_X_FORWARDED_FOR'];
	} else {
		$the_ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
	}
	return $the_ip;
}

// Check if user has access
function is_allowed_access($resource = null) {
	if($resource && isset($_COOKIE['auth_token']) && !empty($_COOKIE['auth_token'])) {
		$userData = auth_verify($_COOKIE['auth_token']);
		if(in_array($resource, $userData['ComponentPath'])) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

// Transliteration
function translit($str) {
	setlocale(LC_CTYPE, 'en_US.utf8');
	$str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
	return $str;
}

// Human-readable file sizes
function human_filesize($bytes, $decimals = 2) {
	$sz = 'BKMGTP';
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

// Human-readable time
function friendly_duration($duration) {
	$s = $duration % 60;
	$m = (floor(($duration%3600)/60)>0)?floor(($duration%3600)/60).' m':'';
	$h = (floor(($duration % 86400) / 3600)>0)?floor(($duration % 86400) / 3600).' h':'';
	$d = (floor(($duration % 2592000) / 86400)>0)?floor(($duration % 2592000) / 86400).' d':'';
	$M = (floor($duration / 2592000)>0)?floor($duration / 2592000).' mth':'';
	return "$M $d $h $m $s s";
}

// Authentication verification
use \Firebase\JWT\JWT;

// Verify authenticating token (JWT)
function auth_verify($jwt) {
	try {
		if(empty($jwt)) {
			throw new Exception('Empty token');
		}

		// Cast decoded JWT to array
		$jwt_decoded = json_decode(json_encode(JWT::decode($jwt, JWT_USER_LOGIN_SECRET, array('HS256'))), true);

		// Check if token has expired
		if($jwt_decoded['exp'] < time()) {
			setcookie('auth_token', '', time()-60, '/', '', true, false);
			return false;
		}

		return $jwt_decoded['data'];

	} catch(\Firebase\JWT\SignatureInvalidException $e) {
		// If signature is invalid, force delete JWT cookie
		setcookie('auth_token', '', time()-60, '/', '', true, false);
		return false;
	} catch(Exception $e) {
		return false;
	}
}

// Simple function to check if user is logged in
function is_logged_in() {
	if(isset($_COOKIE['auth_token']) && !empty($_COOKIE['auth_token'])) {
		return auth_verify($_COOKIE['auth_token']);
	} else {
		return false;
	}
}

// Create API access token
function create_api_access_token($token_seed = null, $time_created = null, $user_salt = null) {
	if(!isset($token_seed)) {
		throw new Exception('Token seed not provided.', 400);
	}
	if(!isset($user_salt)) {
		throw new Exception('User identifier not provided.', 400);
	}

	$jwtData = [
		'iat' => isset($time_created) ? $time_created : time(),
		'data' => array(
			'user' => $user_salt,
			'access_token' => $token_seed
			)
	];

	$secretKey = JWT_SECRET;
	$jwt = JWT::encode(
		$jwtData,
		$secretKey,
		'HS256'
		);

	return $jwt;
}

// Check request URI to prevent open redirects
function is_valid_request_uri($url) {
	if(
		!empty($url) &&
		!preg_match('/^\/[^\/]+/', $url)
		) {
		return true;
	}

	return false;
}

function get_date_from_timestamp($timestamp = null, $html = false) {
	$timestamp = $timestamp ?: time();
	return get_formatted_timestamp($timestamp, 'F j, Y', $html);
}

function get_formatted_timestamp($timestamp = null, $format = DateTime::RFC850, $html = false) {
	$timestamp = $timestamp ?: time();
	$date = DateTime::createFromFormat('U', $timestamp);
	$date->setTimezone(new DateTimeZone('Europe/Copenhagen'));

	$formattedDate = $date->format($format);
	$rfc850FormattedDate = $date->format(DateTime::RFC850);

	$out = $formattedDate;
	if ($html) {
		$out = sprintf('<time datetime="%s">%s</time>', $rfc850FormattedDate, $formattedDate);
	}

	return $out;
}

?>