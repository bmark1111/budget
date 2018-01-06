<?php
/*
 * REST Transaction controller
 */

require_once ('rest_controller.php');

class transaction_controller Extends rest_controller {

	protected $debug = TRUE;

	public function __construct() {
		parent::__construct();
	}

	public function index() {
		$this->ajax->addError(new AjaxError("403 - Forbidden (transaction/index)"));
		$this->ajax->output();
	}

	public function loadAll() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (transaction/loadAll)"));
			$this->ajax->output();
		}

		$params = $this->input->get();

		$date				= (!empty($params['date'])) ? $params['date']: FALSE;
		$description		= (!empty($params['description'])) ? $params['description']: FALSE;
		$amount				= (!empty($params['amount'])) ? $params['amount']: FALSE;
		$vendor				= (!empty($params['vendor'])) ? $params['vendor']: FALSE;
		$bank_account_id	= (!empty($params['bank_account_id'])) ? $params['bank_account_id']: FALSE;
		$category_id		= (!empty($params['category_id'])) ? $params['category_id']: FALSE;
		$pagination_amount	= (!empty($params['pagination_amount'])) ? $params['pagination_amount']: 20;
		$pagination_start	= (!empty($params['pagination_start'])) ? $params['pagination_start']: 0;
		$sort				= (!empty($params['sort'])) ? $params['sort']: 'transaction.transaction_date';
		$sort_dir			= (!empty($params['sort_dir']) && $params['sort_dir'] == 'DESC') ? 'DESC': 'ASC';

		$join = false;
		$join2 = false;
		$transactions = new transaction();
		$transactions->select('SQL_CALC_FOUND_ROWS transaction.*', FALSE);
		if ($date) {
			$date = date('Y-m-d', strtotime($date));
			$transactions->where('transaction.transaction_date', $date);
		}
		if ($description) {
			$transactions->like('transaction.description', $description);
		}
		if ($amount) {
			if (!$join) {
				$transactions->join('transaction_split', 'transaction.id = transaction_split.transaction_id', 'left');
				$join = true;
			}
			$transactions->groupStart();
			$transactions->orWhere('transaction.amount', $amount);
			$transactions->orWhere('transaction_split.amount', $amount);
			$transactions->groupEnd();
		}
		if ($bank_account_id) {
			$transactions->where('transaction.bank_account_id', $bank_account_id);
		}
		if ($category_id) {
			if (!$join) {
				$transactions->join('transaction_split', 'transaction.id = transaction_split.transaction_id', 'left');
				$join = true;
			}
			$transactions->groupStart();
			$transactions->orWhere('transaction.category_id', $category_id);
			$transactions->orWhere('transaction_split.category_id', $category_id);
			$transactions->groupEnd();
		}
		if ($vendor) {
			$transactions->join('vendor V1', 'V1.id = transaction.vendor_id', 'left');
			if (!$join) {
				$transactions->join('transaction_split', 'transaction.id = transaction_split.transaction_id AND transaction_split.is_deleted = 0', 'left');
				$join = true;
			}
			if ($join && !$join2) {
				$transactions->join('vendor V2', 'V2.id = transaction_split.vendor_id AND V2.is_deleted = 0', 'left');
				$join2 = true;
			}
			$transactions->groupStart();
			$transactions->orLike('V1.name', $vendor, 'both');
			if ($join2) {
				$transactions->orLike('V2.name', $vendor, 'both');
			}
			$transactions->groupEnd();
		}
		$transactions->where('transaction.is_deleted', 0);
		$transactions->limit($pagination_amount, $pagination_start);
		$transactions->groupBy('transaction.id');
		$transactions->orderBy($sort, $sort_dir);
		$transactions->orderBy('transaction.id', 'DESC');
		$transactions->result();

		$this->ajax->setData('total_rows', $transactions->foundRows());

		foreach ($transactions as $transaction) {
			isset($transaction->category);
			isset($transaction->bank_account);
			isset($transaction->vendor);
		}
		$this->ajax->setData('result', $transactions);
		$this->ajax->output();
	}

	public function edit() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (transaction/edit)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid transaction id - " . $id . " (transaction/edit)"));
			$this->ajax->output();
		}

		$transaction = new transaction($id);
		if(!empty($transaction->splits)) {
			foreach ($transaction->splits as $split) {
				isset($split->vendor);
			}
		}
		isset($transaction->repeat);
		isset($transaction->vendor);
		
		$this->ajax->setData('result', $transaction);

		$this->ajax->output();
	}

	public function save() {
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (transaction/save)"));
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

		$id = (!empty($_POST['id'])) ? $_POST['id']: FALSE;
		$transaction = new transaction($id);
		$bank_account_id	= (!empty($transaction->bank_account_id)) ? $transaction->bank_account_id: FALSE;
		$transaction_date	= (!empty($transaction->transaction_date)) ? $transaction->transaction_date: FALSE;
		$amount				= (!empty($transaction->amount)) ? $transaction->amount: FALSE;
		$type				= (!empty($transaction->type)) ? $transaction->type: FALSE;

		if (in_array('admin', $this->nRoles) || ($transaction->is_reconciled != 1 && $transaction->is_uploaded != 1)) {
			// can't edit these fields if uploaded or reconciled, only if admin (use with caution)
			$transaction->transaction_date	= date('Y-m-d', strtotime($_POST['transaction_date']));
			$transaction->type				= $_POST['type'];
			$transaction->amount			= $_POST['amount'];
			$transaction->bank_account_id	= $_POST['bank_account_id'];
			$transaction->description		= $_POST['description'];
			$transaction->check_num			= (!empty($_POST['check_num'])) ? $_POST['check_num']: NULL;
		} elseif ($transaction->is_reconciled != 1 && $transaction->is_uploaded == 1) {
			// if transaction is not reconciled but uploaded allow account id to be changed
			$transaction->bank_account_id	= $_POST['bank_account_id'];
		}
		if (empty($id)) {					// if this is a new transaction then .....
			$transaction->bank_account_balance = $_POST['amount'];		// .... set default balance
		}
		$transaction->notes				= (!empty($_POST['notes'])) ? $_POST['notes']: NULL;
		if (empty($_POST['splits'])) {
			// if this is a transfer/payment then set vendor_id to 0
			$transaction->vendor_id = ($_POST['category_id'] != 17) ? $_POST['vendor_id']: 0;
		} else {
			$transaction->vendor_id = NULL;	// ignore vendor_id if splits are present
		}
//		$transaction->vendor_id			= (empty($_POST['splits'])) ? $_POST['vendor_id']: NULL;	// ignore vendor_id if splits are present
		$transaction->category_id		= (empty($_POST['splits'])) ? $_POST['category_id']: NULL;	// ignore category if splits are present
		$transaction->save();

		if (!empty($_POST['splits'])) {
			foreach ($_POST['splits'] as $split) {
				$splitId = (!empty($split['id'])) ? $split['id']: NULL;
				$transaction_split = new transaction_split($splitId);
				if (empty($split['is_deleted']) || $split['is_deleted'] != 1) {
					$transaction_split->amount			= $split['amount'];
					$transaction_split->transaction_id	= $transaction->id;
					$transaction_split->type			= $split['type'];
					$transaction_split->category_id		= $split['category_id'];
					$transaction_split->vendor_id		= ($split['category_id'] != 17) ? $split['vendor_id']: 0;	//$split['vendor_id'];
					$transaction_split->notes			= (!empty($split['notes'])) ? $split['notes']: NULL;
					$transaction_split->save();
				} else {
					$transaction_split->delete();
				}
			}
		}

		/*
		 * if the transaction will affect the account balances then reset account balances
		 * if the amount or date or type or bank account changed then reset account balances
		 */
		if ($amount !== $transaction->amount || $transaction_date !== $transaction->transaction_date || $type !== $transaction->type  || $bank_account_id !== $transaction->bank_account_id) {
			$resetBalances = array();
			// if the bank account changed then reset account balances
			if ($bank_account_id && $bank_account_id !== $transaction->bank_account_id) {
				// if we changed the account then reset balance for original account
				if (!$transaction_date || strtotime($transaction->transaction_date) < strtotime($transaction_date)) {
					$date = $transaction->transaction_date;
				} else {
					$date = $transaction_date;
				}
				$resetBalances[$bank_account_id] = $date;
			}
			if (!$transaction_date || strtotime($transaction->transaction_date) < strtotime($transaction_date)) {
				$date = $transaction->transaction_date;
			} else {
				$date = $transaction_date;
			}
			$resetBalances[$transaction->bank_account_id] = $date;
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

	/**
	 * Checks if splits are entered, if not main vendor_id is a required field
	 */
	public function isValidVendor() {
		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// if no splits then vendor_id is required otherwise MUST be NULL (will be ignored in Save)
		// unless its a transfer/payment (category_id == 17) then no vendor_id required
		if (empty($_POST['splits']) && (empty($_POST['vendor_id']) && $_POST['category_id'] != 17)) {
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
// TODO: for bcmul use either floatval or strval method -> intval( floatval( strval( $n * 100 )  ) / 100 );
		if (!empty($_POST['splits'])) {
//			$split_total = intval(bcmul($_POST['amount'] * 100));
			$split_total = intval(strval($_POST['amount'] * 100));
			foreach ($_POST['splits'] as $split) {
				if (empty($split['is_deleted']) || $split['is_deleted'] != '1') {
					switch ($split['type']) {
						case 'DEBIT':
						case 'CHECK':
						case 'SALE':
							if ($_POST['type'] == 'DEBIT' || $_POST['type'] == 'CHECK' || $_POST['type'] == 'SALE') {
								$split_total -= intval(strval($split['amount'] * 100));
							} else {
								$split_total += intval(strval($split['amount'] * 100));
							}
							break;
						case 'CREDIT':
						case 'DSLIP':
						case 'RETURN':
						case 'PAYMENT':
							if ($_POST['type'] == 'CREDIT' || $_POST['type'] == 'DSLIP' OR $_POST['type'] == 'RETURN' || $_POST['type'] == 'PAYMENT') {
								$split_total -= intval(strval($split['amount'] * 100));
							} else {
								$split_total += intval(strval($split['amount'] * 100));
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
			$this->ajax->addError(new AjaxError("403 - Forbidden (transaction/delete)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid transaction id - " . $id . " (transaction/delete)"));
			$this->ajax->output();
		}
		
		$transaction = new transaction($id);
		$transaction_date = $transaction->transaction_date;
		$bank_account_id = $transaction->bank_account_id;
		if ($transaction->numRows()) {
			if (!empty($transaction->splits)) {
				foreach ($transaction->splits as $split) {
					$split->delete();
				}
			}
			$transaction->delete();

			$this->resetBalances(array($bank_account_id => $transaction_date));	// adjust the account balance from this transaction forward
		} else {
			$this->ajax->addError(new AjaxError("Invalid transaction - (transaction/delete)"));
		}
		$this->ajax->output();
	}

}

// EOF