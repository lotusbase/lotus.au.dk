<?php

namespace LotusBase;

/* DataReturn */
class DataReturn {

	private $dataReturn_vars = array(
		'success' => true,
		'status' => 200,
		'header' => 'Content-Type: application/json'
	);

	// Set status
	public function set_status($status) {
		$this->dataReturn_vars['status'] = intval($status);
	}

	// Set message
	public function set_message($message) {
		$this->dataReturn_vars['message'] = $message;
	}

	// Set data
	public function set_data($data) {
		$this->dataReturn_vars['data'] = $data;
	}

	public function execute() {
		header($this->dataReturn_vars['header']);
		echo json_encode($this->dataReturn_vars);
		exit();
	}
}
?>