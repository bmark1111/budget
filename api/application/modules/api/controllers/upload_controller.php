<?php
/**
 * REST Upload Transaction controller
 */

require_once ('rest_controller.php');

class upload_controller Extends rest_controller {

	protected $debug = TRUE;

	public function __construct() {
		parent::__construct();
	}

	public function index() {
//		$this->ajax->set_header("Forbidden", '403');
		$this->ajax->addError(new AjaxError("403 - Forbidden (upload/index)"));
		$this->ajax->output();
	}

	public function loadAll() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (upload/loadAll)"));
			$this->ajax->output();
		}

		$params = $this->input->get();
		$this->_loadAll($params);
	}
	
	private function _loadAll($params) {
		$status				= (!empty($params['status'])) ? $params['status']: FALSE;
		$date				= (!empty($params['date'])) ? date('Y-m-d', strtotime($params['date'])): FALSE;
		$description		= (!empty($params['description'])) ? $params['description']: FALSE;
		$amount				= (!empty($params['amount'])) ? $params['amount']: FALSE;
		$pagination_amount	= (!empty($params['pagination_amount'])) ? $params['pagination_amount']: 20;
		$pagination_start	= (!empty($params['pagination_start'])) ? $params['pagination_start']: 0;
		$sort				= (!empty($params['sort'])) ? $params['sort']: 'transaction_date';
		$sort_dir			= (!empty($params['sort_dir']) && $params['sort_dir'] == 'DESC') ? 'DESC': 'ASC';

		$transactions = new transaction_upload();
		$transactions->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$transactions->whereNotDeleted();
		if ($status) {
			$status -= 1;
			$transactions->where('status', $status);
		}
		if ($date) {
			$transactions->where('transaction_date', $date);
		}
		if ($description) {
			$transactions->like('description', $description);
		}
		if ($amount) {
			$transactions->where('amount', $amount);
		}
		$transactions->limit($pagination_amount, $pagination_start);
		$transactions->orderBy($sort, $sort_dir);
		$transactions->orderBy('id', 'DESC');
		$transactions->result();

		$this->ajax->setData('total_rows', $transactions->foundRows());

		if ($transactions->numRows()) {
			foreach ($transactions as $transaction) {
				isset($transaction->category);
				isset($transaction->bank_account);
			}
			$this->ajax->setData('result', $transactions);

			// now set the pending count
			$transactions = new transaction_upload();
			$transactions->select('count(*) as count');
			$transactions->whereNotDeleted();
			$transactions->where('status', 0);
			$transactions->row();
			$this->ajax->setData('pending_count', $transaction->count);
		} else {
			$this->ajax->addError(new AjaxError("No uploads found"));
		}
		$this->ajax->output();
	}

	public function assign() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (upload/assign)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id < 0) {
			$this->ajax->addError(new AjaxError("Invalid transaction id - (upload/assign)"));
			$this->ajax->output();
		}

		$uploaded = new transaction_upload($id);
		if ($uploaded->numRows()) {
			// find any current transactions that could mnatch this uploaded transaction
			isset($uploaded->bank_account);
			isset($uploaded->bank_account->bank);

			$this->ajax->setData('result', $uploaded);

			$transactions = new transaction();
			$transactions->whereNotDeleted();
			switch($uploaded->type) {
				case 'CREDIT':
				case 'DSLIP':
					$transactions->whereIn('type', array('DSLIP', 'CREDIT'));
					break;
				case 'DEBIT':
				case 'CHECK':
					$transactions->whereIn('type', array('DEBIT', 'CHECK'));
					break;
			}
			$sd = date('Y-m-d', strtotime($uploaded->transaction_date . " -7 DAYS"));
			$ed = date('Y-m-d', strtotime($uploaded->transaction_date . " +7 DAYS"));
			$transactions->where('transaction_date >= ', $sd);
			$transactions->where('transaction_date <= ', $ed);
			$transactions->where('ROUND(amount)', intval($uploaded->amount), FALSE);
			$transactions->orderBy('transaction_date', 'DESC');
			$transactions->result();
			foreach ($transactions as $transaction) {
				isset($transaction->category);
				isset($transaction->bank_account->bank);
			}

			$this->ajax->setData('transactions', $transactions);
		} else {
			$this->ajax->addError(new AjaxError("Uploaded transaction not found - " . $id));
		}

		$this->ajax->output();
	}

	public function post() {
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (upload/post)"));
			$this->ajax->output();
		}

		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// VALIDATION
		$this->form_validation->set_rules('transaction_date', 'Date', 'required');
		$this->form_validation->set_rules('description', 'Description', 'required');
		$this->form_validation->set_rules('type', 'Type', 'required');
		$this->form_validation->set_rules('category_id', 'Category', 'required|interger');
		$this->form_validation->set_rules('amount', 'Amount', 'required');
		$this->form_validation->set_rules('bank_account_id', 'Bank Account', 'required|interger');
		$this->form_validation->set_rules('id', 'Uploaded', 'required|interger');

		if ($this->form_validation->ajaxRun('') === FALSE) {
			$this->ajax->output();
		}

		$uploaded = new transaction_upload($_POST['id']);
		if ($uploaded->numRows()) {
			$uploaded->status = (!empty($_POST['id'])) ? 1: 2;			// set uploaded transaction as added as new or overwrite for existing
			$uploaded->save();

			$transaction_id = (!empty($_POST['transaction_id'])) ? $_POST['transaction_id']: FALSE;
			$transaction = new transaction($transaction_id);
			$transaction_date	= (!empty($transaction->transaction_date)) ? $transaction->transaction_date: FALSE;
			$transaction->transaction_date	= date('Y-m-d', strtotime($_POST['transaction_date']));
			$transaction->description		= $_POST['description'];
			$transaction->type				= $_POST['type'];
			$transaction->category_id		= $_POST['category_id'];
			$transaction->amount			= $_POST['amount'];
			$transaction->check_num			= (!empty($_POST['check_num'])) ? $_POST['check_num']: NULL;
			$transaction->notes				= (!empty($_POST['notes'])) ? $_POST['notes']: NULL;
			$transaction->bank_account_id	= $_POST['bank_account_id'];
			$transaction->is_uploaded		= 1;
			$transaction->save();

//			// resets account balances
			if ($transaction_date && strtotime($transaction_date) <= strtotime($transaction->transaction_date)) {
				$this->resetBalances(array($transaction->bank_account_id => $transaction_date));	// adjust the account balance from this transaction forward
			} else {
				$this->resetBalances(array($transaction->bank_account_id => $transaction->transaction_date));	// adjust the account balance from this transaction forward
			}
		} else {
			$this->ajax->addError(new AjaxError("403 - Invalid uploaded transaction (upload/post) - " . $_POST['id']));
		}

		$this->ajax->output();
	}

	public function delete() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (upload/delete)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid transaction id - (upload/delete) - " . $id));
			$this->ajax->output();
		}

		$transaction = new transaction_upload($id);
		if ($transaction->numRows()) {
			$transaction->delete();
		} else {
			$this->ajax->addError(new AjaxError("Invalid transaction - (upload/delete) - " . $id));
		}
		$this->ajax->output();
	}

	public function counts() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (upload/delete)"));
			$this->ajax->output();
		}

		$transactions = new transaction_upload();
		$transactions->select('count(*) as count');
		$transactions->whereNotDeleted();
		$transactions->where('status', 0);
		$transactions->row();

		$this->ajax->setData('count', $transactions->count);
		$this->ajax->output();
	}

}

// EOF