<?php

namespace LotusBase;

class CSRFTokenVerificationException extends \Exception {};

class CSRFProtector {

	private $_token = '';
	private $_exception_message = 'Cross Site Request Forgery (CSRF) token invalid. If this error persist, please contact the system administrator.';

	// Constructor
	public function __construct() {
		// If token is not defined, create a new one
		if (!isset($_COOKIE['CSRF_token'])) {
			$this->_generate_token();
		} else {
			$this->_token = $_COOKIE['CSRF_token'];
		}
	}

	// Public function: Get CSRF token
	public function get_token() {
		// Return token
		return $this->_token;
	}

	// Public function: Verify CSRF token
	public function verify_token() {
		// Throw error if:
		// - token is not found in POST data
		// - token is not found in cookie
		// - token from POST data and cookie does not match
		if (
			empty($_POST['CSRF_token']) ||
			empty($_COOKIE['CSRF_token']) ||
			!hash_equals($_POST['CSRF_token'], $_COOKIE['CSRF_token'])
			) {
			throw new CSRFTokenVerificationException($this->_exception_message);
		}
	}

	// Private function: Generate CSRF token
	private function _generate_token() {
		$this->_token = bin2hex(openssl_random_pseudo_bytes(16));

		// Store token in cookie at the same time, allow it to expire after one hour
		setcookie('CSRF_token', $this->_token, time() + 60 * 60);
	}

}
?>