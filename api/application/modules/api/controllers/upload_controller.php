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
		$this->ajax->addError(new AjaxError("403 - Forbidden (upload/index)"));
		$this->ajax->output();
	}

	public function loadAll() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
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
			$this->ajax->setData('pending_count', $transactions->count);
		} else {
			$this->ajax->addError(new AjaxError("No uploads found"));
		}
		$this->ajax->output();
	}

	public function assign() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
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
			$transactions->where('amount', $uploaded->amount);
			$transactions->orderBy('transaction_date', 'DESC');
			$transactions->result();
			foreach ($transactions as $transaction) {
				isset($transaction->category);
				isset($transaction->bank_account->bank);
				isset($transaction->vendor);
				if (!empty($transaction->splits)) {
					foreach ($transaction->splits as $split) {
						isset($split->vendor);
						isset($split->category);
					}
				}
			}

			$this->ajax->setData('transactions', $transactions);
		} else {
			$this->ajax->addError(new AjaxError("Uploaded transaction not found - " . $id));
		}

		$this->ajax->output();
	}

	public function post() {
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (upload/post)"));
			$this->ajax->output();
		}

		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// VALIDATION
		$this->form_validation->set_rules('bank_account_id', 'Bank Account', 'required|integer');
		$this->form_validation->set_rules('transaction_date', 'Date', 'required');
		$this->form_validation->set_rules('description', 'Description', 'required|max_length[150]');
		$this->form_validation->set_rules('type', 'Type', 'required|alpha');
		$this->form_validation->set_rules('category_id', 'Category', 'callback_isValidCategory');
		$this->form_validation->set_rules('vendor_id', 'Vendor', 'callback_isValidVendor');
		$this->form_validation->set_rules('amount', 'Amount', 'callback_isValidAmount');
		$this->form_validation->set_rules('id', 'Uploaded', 'required|integer');

		// validate split data
		if (!empty($_POST['splits'])) {
			foreach ($_POST['splits'] as $idx => $split) {
				if (empty($split['is_deleted']) || $split['is_deleted'] != 1) {
					$this->form_validation->set_rules('splits[' . $idx . '][amount]', 'Split Amount', 'required');
					$this->form_validation->set_rules('splits[' . $idx . '][type]', 'Split Type', 'required|alpha');
					$this->form_validation->set_rules('splits[' . $idx . '][category_id]', 'Split Category', 'required|integer');
					$this->form_validation->set_rules('splits[' . $idx . '][vendor_id]', 'Split Vendor', 'required|integer');
				}
			}
		}

		if ($this->form_validation->ajaxRun('') === FALSE) {
			$this->ajax->output();
		}

		$uploaded = new transaction_upload($_POST['id']);
		if ($uploaded->numRows()) {
			$uploaded->status = (!empty($_POST['id'])) ? 1: 2;			// set uploaded transaction as added as new or overwrite for existing
			$uploaded->save();

			// now save the transaction, possibly overwriting an existing transaction
			$transaction_id = (!empty($_POST['transaction_id'])) ? $_POST['transaction_id']: NULL;
			$transaction = new transaction($transaction_id);
			$transaction_date	= (!empty($transaction->transaction_date)) ? $transaction->transaction_date: FALSE;
			$transaction->transaction_date	= date('Y-m-d', strtotime($_POST['transaction_date']));
			$transaction->description		= $_POST['description'];
			$transaction->type				= $_POST['type'];
			$transaction->vendor_id			= (empty($_POST['splits'])) ? $_POST['vendor_id']: NULL;	// ignore vendor_id if splits are present
			$transaction->category_id		= (empty($_POST['splits'])) ? $_POST['category_id']: NULL;	// ignore category if splits are present
			$transaction->amount			= $_POST['amount'];
			$transaction->check_num			= (!empty($_POST['check_num'])) ? $_POST['check_num']: NULL;
			$transaction->notes				= (!empty($_POST['notes'])) ? $_POST['notes']: NULL;
			$transaction->bank_account_id	= $_POST['bank_account_id'];
			$transaction->is_uploaded		= 1;
			$transaction->save();

			if (!empty($_POST['splits'])) {
				foreach ($_POST['splits'] as $split) {
					$transaction_split = new transaction_split($split['id']);
					if (empty($split['is_deleted']) || $split['is_deleted'] != 1) {
						$transaction_split->amount			= $split['amount'];
						$transaction_split->transaction_id	= $transaction->id;
						$transaction_split->type			= $split['type'];
						$transaction_split->category_id		= $split['category_id'];
						$transaction_split->vendor_id		= $split['vendor_id'];
						$transaction_split->notes			= (!empty($split['notes'])) ? $split['notes']: NULL;
						$transaction_split->save();
					} else {
						$transaction_split->delete();
					}
				}
			}

			// resets account balances, 'resetBalances' method will determine the earlier
			if ($transaction_date) {	// if we are overwriting a transaction then give this transaction date to resetBalances method
				$this->resetBalances(array($transaction->bank_account_id => $transaction_date));			// adjust the account balance from the overwritten transaction forward
			}
			$this->resetBalances(array($transaction->bank_account_id => $transaction->transaction_date));	// adjust the account balance from the new transaction forward

			// check if this is a repeat transaction
			$transaction_repeat = new transaction_repeat();
			$transaction_repeat->whereNotDeleted();
			$transaction_repeat->groupStart();
			$transaction_repeat->orWhere('exact_match', 0);
			$transaction_repeat->orWhere('amount', $_POST['amount']);
			$transaction_repeat->groupEnd();
			$transaction_repeat->where('type', $_POST['type']);
			$transaction_repeat->where('bank_account_id', $_POST['bank_account_id']);
			$transaction_repeat->where('vendor_id', $_POST['vendor_id']);
			$transaction_repeat->where('first_due_date <= now()', NULL);
			$transaction_repeat->where('next_due_date <= ', $_POST['transaction_date']);
			$transaction_repeat->groupStart();
			$transaction_repeat->orWhere('last_due_date IS NULL', NULL, FALSE);
			$transaction_repeat->orWhere('last_due_date >= ', $_POST['transaction_date'], FALSE);
			$transaction_repeat->groupEnd();
			$transaction_repeat->result();
$lq = $transaction_repeat->lastQuery();
			if ($transaction_repeat->numRows()) {
				// repeat transaction
				if ($transaction_repeat->numRows() > 1) {
					log_message('error', 'More than one repeat transaction found');
					throw new Exception('More than one repeat transaction found');
				} else {
					// we found a repeat so update the next_due_date
					$transaction_repeat[0]->next_due_date = date("Y-m-d", strtotime($transaction_repeat[0]->next_due_date . " +" . $transaction_repeat[0]->every . " " . $transaction_repeat[0]->every_unit));
log_message('error', '===========================================FOUND A REPEAT');
log_message('error', 'id = ' . $transaction_repeat[0]->id);
log_message('error', 'bank_account_id = ' . $transaction_repeat[0]->bank_account_id);
log_message('error', 'vendor_id = ' . $transaction_repeat[0]->vendor_id);
log_message('error', 'transaction_date = ' . $transaction_repeat[0]->transaction_date);
log_message('error', 'amount = ' . $transaction_repeat[0]->amount);
log_message('error', "next_due_date = " . $transaction_repeat[0]->next_due_date);
log_message('error', '===========================================================');
					$transaction_repeat[0]->save();
				}
} else {
log_message('error', '--------------------------------------DID NOT FIND A REPEAT');
log_message('error', 'LAST QUERY = ' . $lq);
log_message('error', 'bank_account_id = ' . $_POST['bank_account_id']);
log_message('error', 'vendor_id = ' . $_POST['vendor_id']);
log_message('error', 'transaction_date = ' . $_POST['transaction_date']);
log_message('error', 'amount = ' . $_POST['amount']);
log_message('error', '---------------------------------------------------------');
			}
		} else {
			$this->ajax->addError(new AjaxError("403 - Invalid uploaded transaction (upload/post) - " . $_POST['id']));
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

	/**
	 * Checks if splits are entered, if not main vendor_id is a required field
	 */
	public function isValidVendor() {
		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// if no splits then vendor_id is required otherwise MUST be NULL (will be ignored in Save)
		if (empty($_POST['splits']) && empty($_POST['vendor_id'])) {
			$this->form_validation->set_message('isValidVendor', 'The Vendor Field is Required');
			return FALSE;
		}
		return TRUE;
	}

	/*
	 * Checks if splits are entered, if not main amount is required
	 *								if it is then checks that split amounts equal transaction amount
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
				$this->form_validation->set_message('isValidAmount', 'The Split amounts do not match the transaction amount');
				return FALSE;
			}
		} elseif (empty($_POST['amount']) || $_POST['amount'] == 0) {
			$this->form_validation->set_message('isValidAmount', 'The Amount Field is Required');
			return FALSE;
		}
		return TRUE;
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