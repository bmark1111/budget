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
		$this->ajax->addError(new AjaxError("403 - Forbidden (user/index)"));
		$this->ajax->output();
	}

	public function load() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (user/load)"));
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
//			foreach ($users as $user) {
//				isset($user->category);
//				isset($user->bank_account);
//			}
			$this->ajax->setData('result', $users);
		} else {
			$this->ajax->addError(new AjaxError("No users found"));
		}
		$this->ajax->output();
	}

	public function edit() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (user/edit)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid user id - " . $id . " (user/edit)"));
			$this->ajax->output();
		}

		$user = new user($id);
		isset($user->splits);
		
		$this->ajax->setData('result', $user);

		$this->ajax->output();
	}

	public function save() {
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (user/save)"));
			$this->ajax->output();
		}

		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// VALIDATION
		$this->form_validation->set_rules('bank_account_id', 'Bank Account', 'required');
		$this->form_validation->set_rules('user_date', 'Date', 'required');
		$this->form_validation->set_rules('description', 'Description', 'required|max_length[150]');
		$this->form_validation->set_rules('type', 'Type', 'required|alpha');
		$this->form_validation->set_rules('category_id', 'Category', 'callback_isValidCategory');
		$this->form_validation->set_rules('amount', 'Amount', 'callback_isValidAmount');

		// validate split data
		if (!empty($_POST['splits'])) {
			foreach ($_POST['splits'] as $idx => $split) {
				if (empty($split['is_deleted']) || $split['is_deleted'] != 1) {
					$this->form_validation->set_rules('splits[' . $idx . '][amount]', 'Split Amount', 'required');
					$this->form_validation->set_rules('splits[' . $idx . '][type]', 'Split Type', 'required|alpha');
					$this->form_validation->set_rules('splits[' . $idx . '][category_id]', 'Split Category', 'required|integer');
				}
			}
		}

		if ($this->form_validation->ajaxRun('') === FALSE) {
			$this->ajax->output();
		}

		$id = (!empty($_POST['id'])) ? $_POST['id']: FALSE;
		$user = new user($id);
		$bank_account_id	= (!empty($user->bank_account_id)) ? $user->bank_account_id: FALSE;
		$user_date	= (!empty($user->user_date)) ? $user->user_date: FALSE;
		$amount				= (!empty($user->amount)) ? $user->amount: FALSE;
		$type				= (!empty($user->type)) ? $user->type: FALSE;
		if ($user->is_reconciled != 1 && $user->is_uploaded != 1) {
			// can't edit these fields if uploaded or reconciled
			$user->user_date	= date('Y-m-d', strtotime($_POST['user_date']));
			$user->type				= $_POST['type'];
			$user->amount			= $_POST['amount'];
			$user->bank_account_id	= $_POST['bank_account_id'];
			$user->description		= $_POST['description'];
			$user->check_num			= (!empty($_POST['check_num'])) ? $_POST['check_num']: NULL;
		} elseif ($user->is_reconciled != 1 && $user->is_uploaded == 1) {
			// if user is not reconciled but uploaded allow account id to be changed
			$user->bank_account_id	= $_POST['bank_account_id'];
		}

		$user->notes				= (!empty($_POST['notes'])) ? $_POST['notes']: '';
		$user->category_id		= (empty($_POST['splits'])) ? $_POST['category_id']: NULL; // ignore category if splits are present
		$user->save();

		if (!empty($_POST['splits'])) {
			foreach ($_POST['splits'] as $split) {
				$user_split = new user_split($split['id']);
				if (empty($split['is_deleted']) || $split['is_deleted'] != 1) {
					$user_split->description		= $split['description'];
					$user_split->amount			= $split['amount'];
					$user_split->user_id	= $user->id;
					$user_split->type			= $split['type'];
					$user_split->category_id		= $split['category_id'];
					$user_split->notes			= $split['notes'];
					$user_split->save();
				} else {
					$user_split->delete();
				}
			}
		}

		/*
		 * if the user will affect the account balances then reset account balances
		 * if the amount or date or type or bank account changed then reset account balances
		 */
		if ($amount !== $user->amount || $user_date !== $user->user_date || $type !== $user->type  || $bank_account_id !== $user->bank_account_id) {
			$resetBalances = array();
			// if the bank account changed then reset account balances
			if ($bank_account_id && $bank_account_id !== $user->bank_account_id) {
				// if we changed the account then reset balance for original account
				if (!$user_date || strtotime($user->user_date) < strtotime($user_date)) {
					$date = $user->user_date;
				} else {
					$date = $user_date;
				}
				$resetBalances[$bank_account_id] = $date;
			}
			if (!$user_date || strtotime($user->user_date) < strtotime($user_date)) {
				$date = $user->user_date;
			} else {
				$date = $user_date;
			}
			$resetBalances[$user->bank_account_id] = $date;
			$this->resetBalances($resetBalances);
		}
		$this->ajax->output();
	}

	/**
	 * Checks if splits are entered, if not main category is a required field
	 */
	public function isValidCategory() {
		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// if no splits then category is required otherwise MUST be NULL (will be ignored in Save)
		if (empty($_POST['splits']) && empty($_POST['category_id'])) {
			$this->form_validation->set_message('isValidCategory', 'The Category Field is Required');
			return FALSE;
		}
		return TRUE;
	}

	/*
	 * Checks if splits are entered, if not main amount is required
	 *								if it is then checks that split amounts equal user amount
	 */
	public function isValidAmount() {
		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		if (!empty($_POST['splits'])) {
			$split_total = intval($_POST['amount'] * 100);
			foreach ($_POST['splits'] as $split) {
				if (empty($split['is_deleted']) || $split['is_deleted'] != '1') {
					switch ($split['type']) {
						case 'DEBIT':
						case 'CHECK':
							if ($_POST['type'] == 'DEBIT' || $_POST['type'] == 'CHECK') {
								$split_total -= intval($split['amount'] * 100);
							} else {
								$split_total += intval($split['amount'] * 100);
							}
							break;
						case 'CREDIT':
						case 'DSLIP':
							if ($_POST['type'] == 'CREDIT' || $_POST['type'] == 'DSLIP') {
								$split_total -= intval($split['amount'] * 100);
							} else {
								$split_total += intval($split['amount'] * 100);
							}
							break;
					}
				}
			}
			if ($split_total != 0) {
				$this->form_validation->set_message('isValidAmount', 'The Split amounts do not match the user amount');
				return FALSE;
			}
		} elseif (empty($_POST['amount']) || $_POST['amount'] == 0) {
			$this->form_validation->set_message('isValidAmount', 'The Split Fields are Required');
			return FALSE;
		}
		return TRUE;
	}

	public function delete() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (user/delete)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid user id - " . $id . " (user/delete)"));
			$this->ajax->output();
		}
		
		$user = new user($id);
		$user_date = $user->user_date;
		$bank_account_id = $user->bank_account_id;
		if ($user->numRows()) {
			if (!empty($user->splits)) {
				foreach ($user->splits as $split) {
					$split->delete();
				}
			}
			$user->delete();

			$this->resetBalances(array($bank_account_id => $user_date));	// adjust the account balance from this user forward
		} else {
			$this->ajax->addError(new AjaxError("Invalid user - (user/delete)"));
		}
		$this->ajax->output();
	}

}

// EOF