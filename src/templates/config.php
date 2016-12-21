<?php

// Load data from config.ini
$config = parse_ini_file("config.ini",true);

//=====================//
// Database connection //
//=====================//
// Common database info
define('DB_HOST', $config['general']['host']);
define('DB_NAME', $config['general']['db']);

// User with only SELECT privielges (for all query purposes)
define('DB_USER', $config['general']['user']);
define('DB_PASS', $config['general']['pass']);

// User with UPDATE and DELETE privileges (for admin backend only)
define('DB_ADMIN_USER', $config['admin']['user']);
define('DB_ADMIN_PASS', $config['admin']['pass']);



//===================//
// Define root paths //
//===================//
// WEB_ROOT
// The directory of the folder, relative to the document root, where Lotus Base is installed in
// Used for link generation
// Update this value if you are installing Lotus Base in a subdirectory
define('WEB_ROOT', $config['paths']['web_root']);

// DOC_ROOT
define('DOC_ROOT', rtrim($config['paths']['doc_root'], '/') . WEB_ROOT);

// DOMAIN_NAME
define('DOMAIN_NAME', $config['paths']['domain_name']);



//=============//
// Mail server //
//=============//
// Mail server config
define('SMTP_MAILSERVER', $config['mail']['smtp_mailserver']);
define('NOREPLY_EMAIL', $config['mail']['noreply_email']);



//=======//
// Paths //
//=======//
define('PYTHON_PATH', $config['paths']['python_path']);
define('NODE_PATH', $config['paths']['node_path']);
define('GATEKEEPER_PATH', $config['paths']['gatekeeper_path']);

// Location of publicly and internally available BLAST databases
// Used by BlastDBMetadata class to dynamically generate list of BLAST databases
define('BLAST_DB_DIR_INTERNAL', $config['paths']['blast_db_dir_internal']);
define('BLAST_DB_DIR_PUBLIC', $config['paths']['blast_db_dir_public']);


//======//
// Misc //
//======//
// Set time zone
date_default_timezone_set('Europe/Copenhagen');

// Session lifespan
define('MAX_SESSION_LIFESPAN', $config['misc']['max_session_lifespan']);
define('MAX_USER_TOKEN_LIFESPAN', $config['misc']['max_user_token_lifespan']);


//==========//
// API Keys //
//==========//
define('MAILCHIMP_API_KEY', $config['apikeys']['mailchimp']);
define('GRECAPTCHA_API_KEY', $config['apikeys']['grecaptcha']);
define('MAPBOX_API_KEY', $config['apikeys']['mapbox']);
define('LOTUSBASE_API_KEY', $config['apikeys']['lotusbase']);


//=========//
// Secrets //
//=========//
define('JWT_SECRET', $config['secrets']['jwt']);


//==========//
// Autoload //
//==========//
require_once(DOC_ROOT.'/vendor/autoload.php');
require_once(DOC_ROOT.'/lib/autoload.php');

?>