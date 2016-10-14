<?php

require_once('../../config.php');

// Use JWT
use \Firebase\JWT\JWT;

// Create container
$c = new \Slim\Container();

// Get for database connection first
try {
	$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	$c['db'] = $db;

	$admindb = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_ADMIN_USER, DB_ADMIN_PASS);
	$admindb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$admindb->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	$c['admindb'] = $admindb;
} catch(PDOException $e) {

}

// Override the default Not Found Handler
$c['notFoundHandler'] = function ($c) {
	return function ($request, $response) use ($c) {
		return $c['response']
			->withStatus(404)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 404,
				'message' => 'Page not found.'
				)));
	};
};

$c['errorHandler'] = function ($c) {
	return function ($request, $response, $exception) use ($c) {
		return $c['response']
			->withStatus(500)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 500,
				'message' => 'Application error. '.$exception->getMessage()
				)));
	};
};


$api = new \Slim\App($c);

// Inject global variables
$c['globalvars'] = array(
	'lj_genome_versions' => $lj_genome_versions,
	'user' => isset($user) ? $user : false
	);

// Middleware for user token authentication
$api->add(new \Slim\Middleware\JwtAuthentication([
	'secret' => JWT_SECRET,
	'path' => ['/admin', '/users'],
	'secure' => true,
	'relaxed' => ['localhost'],
	'attribute' => 'user_auth_token',
	'error' => function($request, $response, $arguments) {
		return $response
			->withStatus(401)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 401,
				'message' => 'User authentication failed. '.$arguments['message']
				)));
	}
	]));

// Middleware for API access token authentication
$api->add(new \Slim\Middleware\JwtAuthentication([
	'secret' => JWT_SECRET,
	'environment' => ['HTTP_X_API_KEY'],
	'header' => 'X-API-KEY',
	'regexp' => '/(.*)/',
	'secure' => true,
	'relaxed' => ['localhost'],
	'passthrough' => ['/', '/cornea/job/data'],
	'path' => ['/'],
	'attribute' => 'access_token',
	'error' => function($request, $response, $arguments) {
		return $response
			->withStatus(401)
			->withHeader('Content-Type', 'application/json')
			->write(json_encode(array(
				'status' => 401,
				'message' => 'Access token verification failed. '.$arguments['message'],
				)));
	},
	'callback' => function($request, $response, $arguments) use ($c) {
		$data = json_decode(json_encode($arguments['decoded']), true)['data'];
		
		// Verify decoded token against database
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$access_token_query = $db->prepare('UPDATE apikeys AS t1
			LEFT JOIN auth AS t2
				ON t1.UserSalt = t2.Salt
			SET t1.LastAccessed = now()
			WHERE t1.UserSalt = ? AND t1.Token = ?');
		$r = $access_token_query->execute(array($data['user'], $data['access_token']));

		// Check if exection is successful and that exactly ONE row is updated
		if($r && $access_token_query->rowCount() === 1) {
			return true;
		} else {
			throw new Exception('Access token is invalid.', 401);
		}
	}
	]));


// Middleware for passing access_token to HTTP header
$api->add(function($request, $response, $next) {
	if (false === empty($request->getQueryParams()["access_token"])) {
		$request = $request->withHeader("X-API-KEY", $request->getQueryParams()["access_token"]);
	}
	return $next($request, $response);
});

// Middleware for CORS headers
$api->add(new \CorsSlim\CorsSlim(array(
	'origin' => '*',
	'maxAge' => 1728000,
	'allowCredentials' => true,
	'allowHeaders' => array('X-API-KEY', 'Authorization')
	)));

// Middleware for sniffing X-FORWARDED-PROTO
$api->add(new RKA\Middleware\SchemeAndHost(['10.29.0.253']));

// Blank
$api->get('/', function ($request, $response) {
	return $response
		->withStatus(200)
		->withHeader('Location', DOMAIN_NAME . WEB_ROOT . '/docs/api/');
});

// Routes for admin
include_once('./routes/admin.php');

// Routes for BLAST requests
include_once('./routes/blast.php');

// Routes for CORx
include_once('./routes/corx.php');

// Routes for ExpAt
include_once('./routes/expat.php');

// Routes for gene functions
include_once('./routes/gene.php');

// Routes for LORE1
include_once('./routes/lore1.php');

// Routes for misc functions
include_once('./routes/misc.php');

// Routes for users
include_once('./routes/users.php');

// Routes for registration
include_once('./routes/registration.php');

// Routes for PhyAlign
include_once('./routes/phyalign.php');

// Routes for view
include_once('./routes/view.php');

$api->run();