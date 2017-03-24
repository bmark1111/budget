<?php
/*
 * REST Bank controller
 */

require_once ('rest_controller.php');

class bank_controller Extends rest_controller {

	protected $debug = TRUE;

	public function __construct() {
		parent::__construct();
	}

	public function index() {
//		$this->ajax->set_header("Forbidden", '403');
		$this->ajax->addError(new AjaxError("403 - Forbidden (bank/index)"));
		$this->ajax->output();
	}

	public function load() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (bank/load)"));
			$this->ajax->output();
		}

		$params = $this->input->get();

		$name				= (!empty($params['name'])) ? $params['name']: FALSE;
		$pagination_amount	= (!empty($params['pagination_amount'])) ? $params['pagination_amount']: 20;
		$pagination_start	= (!empty($params['pagination_start'])) ? $params['pagination_start']: 0;
		$sort				= (!empty($params['sort'])) ? $params['sort']: 'name';
		$sort_dir			= (!empty($params['sort_dir']) && $params['sort_dir'] == 'DESC') ? 'DESC': 'ASC';

		$banks = new bank();
		if ($name) {
			$banks->like('name', $name);
		}
		$banks->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$banks->whereNotDeleted();
		$banks->limit($pagination_amount, $pagination_start);
		$banks->orderBy($sort, $sort_dir);
		$banks->result();
		if ($banks->numRows()) {
//			isset($bank_account->bank);

			$this->ajax->setData('result', $banks);
		} else {
			$this->ajax->addError(new AjaxError("No banks found"));
		}
		$this->ajax->output();
	}

	public function edit() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (bank/edit)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid bank id - " . $id . " (bank/edit)"));
			$this->ajax->output();
		}

		$banks = new bank($id);
		if ($banks->accounts) {
			foreach ($banks->accounts as $account){
				isset($account->balance);
			}
		}
		
		$this->ajax->setData('result', $banks);

		$this->ajax->output();
	}

	public function save() {
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (bank/save)"));
			$this->ajax->output();
		}

		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// VALIDATION
		$this->form_validation->set_rules('name', 'Bank Name', 'required');

		// validate account data
		foreach ($_POST['accounts'] as $idx => $account) {
			if (empty($account['is_deleted']) || $account['is_deleted'] != 1) {
				$this->form_validation->set_rules('accounts[' . $idx . '][name]', 'Name', 'required');
				$this->form_validation->set_rules('accounts[' . $idx . '][date_opened]', 'Date Opened', 'required');
//				$this->form_validation->set_rules('accounts[' . $idx . '][balance]', 'Balance', 'numeric|xss_clean');
			}
		}

		if ($this->form_validation->ajaxRun('') === FALSE) {
			$this->ajax->output();
		}

		$id = (!empty($_POST['id'])) ? $_POST['id']: null;
		$bank = new bank($id);
		$bank->name	= $_POST['name'];
		$bank->save();

		foreach ($_POST['accounts'] as $account) {
			if (empty($account['is_deleted']) || $account['is_deleted'] != 1) {
				$account_id = (!empty($account['id'])) ? $account['id']: null;
				$bank_account = new bank_account($account_id);
				$bank_account->bank_id		= $bank->id;
				$bank_account->name			= $account['name'];
				$bank_account->date_opened	= date('Y-m-d', strtotime($account['date_opened']));
				$bank_account->date_closed	= (!empty($account['date_closed'])) ? date('Y-m-d', strtotime($account['date_closed'])): null;
				$bank_account->save();

				$balance_transaction_id = (!empty($account['balance_transaction_id'])) ? $account['balance_transaction_id']: null;
				$transaction = new transaction($balance_transaction_id);
				$transaction->bank_account_id = $bank_account->id;
				if (empty($account['balance']['id'])) {
					// if creating balance transaction then set transaction date
					$transaction->transaction_date = date('Y-m-d');
				}
//				$transaction->type					= (floatVal($account['balance']['amount']) > 0) ? 'CREDIT': 'DEBIT';
//				$transaction->amount				= (!empty($account['balance']['amount'])) ? floatVal($account['balance']['amount']): 0;
//				$transaction->bank_account_balance	= (!empty($account['balance']['amount'])) ? floatVal($account['balance']['amount']): 0;//$transaction->amount;
				if (empty($account['balance']['bank_account_balance'])) {
					$transaction->type					= 'DEBIT';
					$transaction->amount				= 0;
					$transaction->bank_account_balance	= 0;
				} else if (floatVal($account['balance']['bank_account_balance']) > 0) {
					$transaction->type					= 'CREDIT';
					$transaction->bank_account_balance	= floatVal($account['balance']['bank_account_balance']);
					$transaction->amount				= $transaction->bank_account_balance;
				} else {
					$transaction->type					= 'DEBIT';
					$transaction->bank_account_balance	= floatVal($account['balance']['bank_account_balance']);
					$transaction->amount				= floatVal(-$account['balance']['bank_account_balance']);
				}
				$transaction->description			= 'Opening Balance for ' . $account['name'] . ' account';
				$transaction->vendor_id				= 1;	// TODO: when adding a bank add it to vendor file, keep vendor id in bank record
				$transaction->category_id			= 22;
				$transaction->save();

				$bank_account = new bank_account($bank_account->id);
				$bank_account->balance_transaction_id = $transaction->id;
				$bank_account->save();

				$this->resetBalances(array($bank_account->id => $transaction->transaction_date));	// adjust the account balance from this transaction forward
			} else {
				$bank_account = new bank_account($account['id']);
				$bank_account->delete();
			}
		}

		$this->ajax->output();
	}

	public function delete() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (bank/delete)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid bank id - " . $id . " (bank/delete)"));
			$this->ajax->output();
		}
		
		$bank = new bank($id);
		if ($bank->numRows()) {
			if (!empty($bank->accounts)) {
				foreach ($bank->accounts as $account) {
					$account->delete();
				}
			}
			$bank->delete();
		} else {
			$this->ajax->addError(new AjaxError("Invalid bank - " . $id . " (bank/delete)"));
		}
		$this->ajax->output();
	}

	public function accounts() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (bank/accounts)"));
			$this->ajax->output();
		}

		$bank_accounts = new bank_account();
		$bank_accounts->whereNotDeleted();
//		$bank_accounts->where('date_closed IS NULL', FALSE, FALSE);
//		$bank_accounts->orderBy('name', 'ASC');
		$bank_accounts->result();
		foreach ($bank_accounts as $bank_account) {
			isset($bank_account->bank);
		}

		$this->ajax->setData('bank_accounts', $bank_accounts);

		$this->ajax->output();
	}

}

// EOF