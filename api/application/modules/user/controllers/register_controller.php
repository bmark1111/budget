<?php

class register_controller extends EP_Controller {

	public function __construct() {
		parent::__construct();
	}

	public function index() {
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (register/index)"));
			$this->ajax->output();
		}

		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		$this->form_validation->set_rules('description', 'Description', 'required|max_length[250]');
		$this->form_validation->set_rules('firstname', 'First Name', 'required|max_length[50]');
		$this->form_validation->set_rules('lastname', 'Last Name', 'required|max_length[50]');
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[100]|callback_isDuplicate');

		if ($this->form_validation->ajaxRun('') === FALSE) {
			$this->ajax->output();
		}

		$account = new account();
		$account->description	= $_POST['description'];
		$account->firstname		= $_POST['firstname'];
		$account->lastname		= $_POST['lastname'];
		$account->email			= $_POST['email'];
		$account->save();

		$this->ajax->output();
	}

	public function isDuplicate($email) {
		$account = new account();
		$account->whereNotDeleted();
		$account->where('email', $email);
		$account->row();
		if ($account->numRows()) {
			$this->form_validation->set_message('isDuplicate', 'Invalid Account request');
			return FALSE;
		}
		return TRUE;
	}
}

// EOF