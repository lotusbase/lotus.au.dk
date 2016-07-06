<?php

namespace LotusBase\Users;
use \PDO;

/* Users\Integrate */
class Integrate {

	private $_vars = array();

	public function setUserData($oauth_userData) {
		$this->_vars['userData'] = $oauth_userData;
	}

	public function setProvider($oauth2_provider) {
		$this->_vars['provider'] = $oauth2_provider;
	}

	public function processUser() {

		// Generate state
		$state = base64_encode(mcrypt_create_iv(8, MCRYPT_DEV_URANDOM));

		// Database connection
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";port=3306;charset=utf8", DB_USER, DB_PASS);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		// Query 1: Check if user already exists, and has linked account
		$q1 = $db->prepare('SELECT * FROM auth WHERE '.$this->_vars['provider'].'ID = ?');
		$q1->execute(array($this->_vars['userData']['ID']));
		if($q1->rowCount()) {
			$userData = $q1->fetch(PDO::FETCH_ASSOC);

			// Create JWT token and store it on the client-side
			$jwt = new \LotusBase\Users\AuthToken();
			$jwt->setUserData($userData);
			$jwt->setValidDuration(MAX_USER_TOKEN_LIFESPAN);
			$jwt->setCookie();

			if(isset($userData['Authority']) && $userData['Authority'] > 3) {
				header('location: '.WEB_ROOT.'/users/');
			} else {
				header('location: '.WEB_ROOT.'/admin/');
			}
			exit();
		}

		// Query 2: Check if email, or first and last names, already exist(s), then offer users to link their accounts
		// Use wildcard on first name, in case the user also included their middle name(s)
		$q2 = $db->prepare('SELECT * FROM auth WHERE Email = ? OR (FirstName LIKE ? AND LastName = ?)');
		$q2->execute(array(
			$this->_vars['userData']['Email'],
			$this->_vars['userData']['FirstName'].'%',
			$this->_vars['userData']['LastName']
		));
		if($q2->rowCount()) {
			$userData = $q2->fetch(PDO::FETCH_ASSOC);

			// Offer user to link accounts
			$_SESSION['user_integration'] = array(
				'OAuth_userData' => $this->_vars['userData'],
				'local_userData' => $userData,
				'diff_userData' => array_diff_assoc(
					array_intersect_key($this->_vars['userData'], array_flip(array('Email','FirstName','LastName'))),
					$userData
					),
				'provider' => $this->_vars['provider'],
				'state' => $state
				);
			header('location: '.WEB_ROOT.'/users/integrate.php?action=link&state='.$state);
			session_write_close();
			exit();
		}

		// Query 3: User is new! Offer to create new account using details from OAuth2 data
		// Offer user to create new account
		$_SESSION['user_integration'] = array(
			'OAuth_userData' => $this->_vars['userData'],
			'local_userData' => $userData,
			'provider' => $this->_vars['provider'],
			'state' => $state
			);
		header('location: '.WEB_ROOT.'/users/integrate.php?action=create&state='.$state);
		session_write_close();
		exit();

	}
}

?>