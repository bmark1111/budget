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

		$this->form_validation->set_rules('firstname', 'First Name', 'required|max_length[50]');
		$this->form_validation->set_rules('lastname', 'Last Name', 'required|max_length[50]');
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[100]|callback_isDuplicate');
		$this->form_validation->set_rules('login', 'Login', 'required|min_length[6]|max_length[30]');
		$this->form_validation->set_rules('password', 'Password', 'required|min_length[8]|max_length[20]|callback_isValidPassword');

		if ($this->form_validation->ajaxRun('') === FALSE) {
			$this->ajax->output();
		}

		$lastAccount = new account();
		$lastAccount->whereNotDeleted();
		$lastAccount->orderBy('id', 'DESC');
		$lastAccount->row();
//Array
//(
//    [id] => 3
//)
//print $lastAccount;
//die;
		$account = new account();
		$account->firstname			= $_POST['firstname'];
		$account->lastname			= $_POST['lastname'];
		$account->email				= $_POST['email'];
		$account->account_num		= ++$lastAccount->account_num;
		$account->db_suffix_name	= $_POST['firstname'][0] . '_' . $account->account_num . '_' . $_POST['lastname'][0];
print $account;
die;
//		$account->save();

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

	public function isValidPassword($password) {
		$has_numeric = false;
		$has_uppercase = false;
		$has_lowercase = false;
		$strlen = strlen( $password );
		for( $i = 0; $i < $strlen; $i++ ) {
			$char = substr( $password, $i, 1 );
			if (is_numeric($char)) {
				$has_numeric = true;
			}
			if (ctype_upper($char)) {
				$has_uppercase = true;
			}
			if (ctype_lower($char)) {
				$has_lowercase = true;
			}
		}
		if (!$has_numeric || !$has_uppercase || !$has_lowercase) {
			$this->form_validation->set_message('isValidPassword', 'Invalid Password');
			return FALSE;
		}
		return TRUE;
	}

}

// EOF