<?php
// Escape HTML
function escapeHTML($str) {
	return htmlspecialchars(@trim($str), ENT_QUOTES, "UTF-8");
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
		return chunk_split($str, 10, ' ');
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
function is_intranet_client() {
	// Check of IP is from localhost
	if(in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) {
		return array('HostName' => 'localhost', 'HostDescription' => null);
	}

	$clientIP = get_ip();
	if($clientIP) {
		try {
			$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			$q = $db->prepare('SELECT HostName, HostDescription FROM host_access WHERE IPstart <= INET_ATON(?) AND IPend >= INET_ATON(?) LIMIT 1');
			$q->execute(array($clientIP, $clientIP));

			$row = $q->fetch(PDO::FETCH_ASSOC);
			return $row;

		} catch(PDOException $e) {
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

// Breadcrumbs
function get_breadcrumbs($opts = null) {
	$crumbs = array_values(array_filter(explode("/",strtok($_SERVER['REQUEST_URI'],'?'))));
	$crumbs_path = $crumbs;
	$crumbs_titles = $crumbs;

	if($opts !== null && is_array($opts)) {
		// Replace last element if $title is defined
		if(isset($opts['page_title']) && is_string($opts['page_title'])) {
			array_pop($crumbs);
			array_push($crumbs, $opts['page_title']);
			$crumbs_titles = $crumbs;
		}

		// Replace titles if custom titles are defined
		if(isset($opts['custom_titles']) && !empty($opts['custom_titles']) && is_array($opts['custom_titles'])) {
			$crumbs_titles = $opts['custom_titles'];
		}

		// Replace entire path if custom breadcrumb is defined
		if(isset($opts['custom_breadcrumb'])) {
			foreach($opts['custom_breadcrumb'] as $title => $path) {
				$custom_breadcrumb_titles[] = $title;
				$custom_breadcrumb_paths[] = $path;
			}
			$crumbs = $custom_breadcrumb_paths;
			$crumbs_path = $crumbs;
			$crumbs_titles = $custom_breadcrumb_titles;
		}
	}

	// Construct breadcrumbs
	$breadcrumbs = '<section class="wrapper" id="breadcrumb"><nav><ul itemscope itemtype="http://schema.org/BreadcrumbList"><li><a href="'.WEB_ROOT.'/" title="Home"><em>Lotus</em> Base</a></li>';
	foreach($crumbs as $key => $crumb){
		// Append
		$crumb_title = ucwords(str_replace(array(".php","_","-"),array(""," ", " "),$crumbs_titles[$key]));
		$breadcrumbs .= '<li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><a href="'.WEB_ROOT.'/'.implode('/', array_slice($crumbs_path, 0, $key+1)).'" title="'.$crumb_title.'" itemprop="item"><span itemprop="name">'.$crumb_title.'</span></a><meta itemprop="position" content="'.($key).'" /></li>';
	}
	return $breadcrumbs .= '</ul></nav></section>';
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
		$jwt_decoded = json_decode(json_encode(JWT::decode($jwt, JWT_SECRET, array('HS256'))), true);

		// Check if token has expired
		if($jwt_decoded['exp'] < time()) {
			setcookie('auth_token', '', time()-60, '/');
			return false;
		}

		return $jwt_decoded['data'];

	} catch(\Firebase\JWT\SignatureInvalidException $e) {
		// If signature is invalid, force delete JWT cookie
		setcookie('auth_token', '', time()-60, '/');
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
?>