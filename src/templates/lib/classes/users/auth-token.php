<?php

namespace LotusBase\Users;

// Use JWT
use \Firebase\JWT\JWT;

/* Users\AuthToken */
class AuthToken {

	// Store private variables in array
	private $_vars = array();

	// Set status
	public function setValidDuration($duration) {
		$this->_vars['validDuration'] = intval($duration);
	}

	// Set user data
	public function setUserData($row) {
		$this->_vars['userData'] = $row;
	}

	// Generate token
	private function _generateToken() {
		$this->_vars['tokenID']		= base64_encode(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
		$this->_vars['issuedAt']	= time();
		$this->_vars['notBefore']	= $this->_vars['issuedAt'];
		$this->_vars['expire']		= $this->_vars['issuedAt'] + $this->_vars['validDuration'];
		$this->_vars['serverName']	= DOMAIN_NAME;

		$jwtData = [
			'iat' => $this->_vars['issuedAt'],
			'jti' => $this->_vars['tokenID'],
			'iss' => $this->_vars['serverName'],
			'nbf' => $this->_vars['notBefore'],
			'exp' => $this->_vars['expire'],
			'data' => array_intersect_key($this->_vars['userData'], array_flip(array(
				'FirstName',
				'LastName',
				'Email',
				'Username',
				'Organization',
				'Salt',
				'Authority',
				'Verified',
				'VerificationKeyTimestamp',
				'UserGroup',
				'UserGroups',
				'ComponentPath'
				)))
		];

		$jwtData['data']['UserGroups'] = array_filter(explode(',', $jwtData['data']['UserGroups']));

		$secretKey = JWT_USER_LOGIN_SECRET;
		$jwt = JWT::encode(
			$jwtData,
			$secretKey,
			'HS256'
			);
		return $jwt;
	}

	// Set cookie
	public function setCookie() {
		setcookie('auth_token', $this->_generateToken(), $this->_vars['expire'], '/', '', true, false);
	}

	// Get token
	public function getToken() {
		return $this->_generateToken();
	}
}
?>