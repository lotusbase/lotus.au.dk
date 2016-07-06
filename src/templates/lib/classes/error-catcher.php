<?php

namespace LotusBase;

/* ErrorCatcher */
class ErrorCatcher {

	private $errorCatcher_vars = array(
		'error' => true,
		'status' => 500,
		'header' => 'Content-Type: application/json',
		'message' => 'An unspecified error has occured. Should this issue persist, please contact the system administrator.'
	);

	// Set status
	public function set_status($status) {
		$this->errorCatcher_vars['status'] = intval($status);
	}

	// Set message
	public function set_message($message) {
		$this->errorCatcher_vars['message'] = $message;
	}

	// Set data
	public function set_data($data) {
		$this->errorCatcher_vars['data'] = $data;
	}

	public function execute() {
		header($this->errorCatcher_vars['header']);
		echo json_encode($this->errorCatcher_vars);
		exit();
	}
}
?>