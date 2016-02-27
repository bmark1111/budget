<?php
/*
 * REST Transaction controller
 */

//require_once ('rest_controller.php');

class user_controller Extends EP_Controller {

	protected $debug = TRUE;

	public function __construct() {
		parent::__construct();
	}

	public function index() {
//		$this->ajax->set_header("Forbidden", '403');
		$this->ajax->addError(new AjaxError("403 - Forbidden (admin/user/index)"));
		$this->ajax->output();
	}

	public function load() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (admin/user/load)"));
			$this->ajax->output();
		}

		$params = $this->input->get();

		$name				= (!empty($params['lastname'])) ? $params['lastname']: FALSE;
		$pagination_amount	= (!empty($params['pagination_amount'])) ? $params['pagination_amount']: 20;
		$pagination_start	= (!empty($params['pagination_start'])) ? $params['pagination_start']: 0;
		$sort				= (!empty($params['sort'])) ? $params['sort']: 'user_date';
		$sort_dir			= (!empty($params['sort_dir']) && $params['sort_dir'] == 'DESC') ? 'DESC': 'ASC';

		$users = new user();
		if ($name) {
			$users->where('lastname', $name);
		}
		$users->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$users->whereNotDeleted();
		$users->limit($pagination_amount, $pagination_start);
		$users->orderBy($sort, $sort_dir);
		$users->result();

		$this->ajax->setData('total_rows', $users->foundRows());

		if ($users->numRows()) {
			$this->ajax->setData('result', $users);
		} else {
			$this->ajax->addError(new AjaxError("No users found"));
		}
		$this->ajax->output();
	}

	public function edit() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (admin/user/edit)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid user id - " . $id . " (admin/user/edit)"));
			$this->ajax->output();
		}
		$user = new user($id);
		$this->ajax->setData('result', $user);

		$this->switchDatabase('budgettr_master');
		$user_sessions = new user_session();
		$user_sessions->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$user_sessions->limit(20, 0);
		$user_sessions->orderBy('request_time', 'DESC');
		$user_sessions->where('user_id', $id);
		$user_sessions->result();
		$this->ajax->setData('sessions', $user_sessions);
		
		$this->ajax->output();
	}

	public function save() {
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (admin/user/save)"));
			$this->ajax->output();
		}

		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// VALIDATION
		$this->form_validation->set_rules('firstname', 'First Name', 'required|max_length[50]');
		$this->form_validation->set_rules('lastname', 'Last Name', 'required|max_length[50]');
		$this->form_validation->set_rules('email', 'Email', 'required|max_length[100]|valid_email');
		$this->form_validation->set_rules('login', 'Login', 'required|alpha_dash');

		if ($this->form_validation->ajaxRun('') === FALSE) {
			$this->ajax->output();
		}

		$user = new user($_POST['id']);
		$user->firstname	= $_POST['firstname'];
		$user->lastname		= $_POST['lastname'];
		$user->login		= $_POST['login'];
		$user->email		= $_POST['email'];
		$user->active		= $_POST['active'];
		if (empty($_POST['id'])) {
			$user->joindate = date('Y-m-d');
		}
		$user->save();

		$this->ajax->output();
	}

	public function delete() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (admin/user/delete)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid user id - " . $id . " (admin/user/delete)"));
			$this->ajax->output();
		}
		
		$user = new user($id);
		$user->delete();

		$this->ajax->output();
	}

}

// EOF