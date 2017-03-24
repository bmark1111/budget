<?php
/*
 * REST Transaction controller
 */

class util_controller Extends EP_Controller {

	protected $debug = TRUE;

	public function __construct() {
		parent::__construct();
	}

	public function index() {
		$params = $this->input->get();
		switch($params['type']) {
			case 'balance':
				$this->_balanceUpdate();
				break;
			case 'assign':
				$this->_assignVendors();
				break;
			case 'migrate':
				$this->_migrate();
				break;
			default:
				$this->ajax->addError(new AjaxError("403 - Forbidden (admin/util/index)"));
		}
		$this->ajax->output();
	}

	private function _assignVendors() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (admin/util/assignVendors)"));
			$this->ajax->output();
		}
		$transactions = new transaction();
		$transactions->whereNotDeleted();
		$transactions->result();
		foreach ($transactions as $transaction) {
			if (strlen($transaction->description) > 10) {
				$strLen = strlen($transaction->description) - 5;
				$transaction_upload = new transaction_upload();
				$transaction_upload->whereNotDeleted();
				$transaction_upload->where('vendor_id IS NULL', null, false);
				$transaction_upload->like('description', substr($transaction->description,0,$strLen), 'after');
				$transaction_upload->result();
echo $transaction_upload->lastQuery();
print $transaction_upload;
die;
				if ($transaction_upload->numRows()) {
					foreach ($transaction_upload as $upload) {
						$upload->vendor_id = $transaction->id;
						$upload->save();
					}
				}
			}
		}
	}

	private function xx_assignVendors() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (admin/util/assignVendors)"));
			$this->ajax->output();
		}

		$transactions = new transaction();
		$transactions->groupStart();
		$transactions->orLike('description', 'online transfer ', 'right');
		$transactions->orLike('description', 'Transfer from ', 'right');
		$transactions->orLike('description', 'interest payment', 'right');
		$transactions->orLike('description', 'service fee');
		$transactions->orLike('description', 'INSUFFICIENT FUNDS FEE', 'right');
		$transactions->orLike('description', 'Opening Deposit');
		$transactions->groupEnd();
		$transactions->result();
		foreach ($transactions as $transaction) {
			$transaction->vendor_id = 258;		// Chase Bank
			$transaction->save();
		}

		$transactions = new transaction();
		$transactions->like('description', 'online transfer %9570', 'right', FALSE);
		$transactions->result();
		foreach ($transactions as $transaction) {
			$transaction->vendor_id = 44;		// Merrielle Markham
			$transaction->save();
		}

		$vendors = new vendor();
		$vendors->result();
		foreach ($vendors as $vendor) {
			$like = str_replace(' ', '%', str_replace('-', '%', $vendor->name));
			$like .= ($vendor->phone_area_code) ? '%' . $vendor->phone_area_code: '';
			$like .= ($vendor->phone_prefix) ? '%' . $vendor->phone_prefix: '';
			$like .= ($vendor->phone_number) ? '%' . $vendor->phone_number: '';
			$like .= ($vendor->street) ? '%' . $vendor->street: '';
			$like .= ($vendor->city) ? '%' . $vendor->city: '';
			$like .= ($vendor->state) ? '%' . $vendor->state: '';
			$transactions = new transaction();
			$transactions->like('description', $like, 'both', FALSE);
			$transactions->result();
			foreach ($transactions as $transaction) {
				if (!$transaction->vendor_id || $transaction->vendor_id == 0) {
					$transaction->vendor_id = $vendor->id;
					$transaction->save();
				}
			}
		}

		foreach ($vendors as $vendor) {
			$like = str_replace(' ', '%', $vendor->name);
			$transactions = new transaction();
			$transactions->where('vendor_id', 0);
			$transactions->groupStart();
			$transactions->orLike('description', $like);
			$transactions->orLike('notes', $like);
			$transactions->groupEnd();
			$transactions->result();
			foreach ($transactions as $transaction) {
				if (!$transaction->vendor_id || $transaction->vendor_id == 0) {
					$transaction->vendor_id = $vendor->id;
					$transaction->save();
				}
			}
		}

		foreach ($vendors as $vendor) {
			$like = str_replace(' ', '%', str_replace('-', '%', $vendor->name));
			$transactions = new transaction();
			$transactions->where('vendor_id', 0);
			$transactions->groupStart();
			$transactions->orLike('description', $like, 'both', FALSE);
			$transactions->orLike('notes', $like, 'both', FALSE);
			$transactions->groupEnd();
			$transactions->result();
//echo $transactions->lastQuery()."\n";
//print $transactions;
//die;
			foreach ($transactions as $transaction) {
				if (!$transaction->vendor_id || $transaction->vendor_id == 0) {
					$transaction->vendor_id = $vendor->id;
					$transaction->save();
				}
			}
		}
		$this->ajax->output();
	}

	private function _balanceUpdate() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (admin/util/balanceUpdate)"));
			$this->ajax->output();
		}

		$bank_account_balances = array();
		$bank_accounts = new bank_account();
		$bank_accounts->result();
		if ($bank_accounts->numRows()) {
			foreach ($bank_accounts as $bank_account) {
				$bank_account_balances[$bank_account->id] = 0;
			}
			$transactions = new transaction();
			$transactions->whereNotDeleted();
			$transactions->orderBy('transaction_date', 'ASC');
			$transactions->orderBy('id', 'ASC');
			$transactions->result();
			if ($transactions->numRows()) {
				foreach ($transactions as $transaction) {
					$amount = 0;
					switch ($transaction->type) {
						case 'DEBIT':
						case 'CHECK':
						case 'SALE':
							$amount -= $transaction->amount;
							break;
						case 'RETURN':
						case 'PAYMENT':
						case 'CREDIT':
						case 'DSLIP':
							$amount += $transaction->amount;
							break;
					}
					$bank_account_balances[$transaction->bank_account_id] += $amount;
					$transaction->bank_account_balance = $bank_account_balances[$transaction->bank_account_id];
					$transaction->save();
				}
			}
		}
		$this->ajax->output();
	}

	/**
	 * 
	 * @param type $categories
	 * @param type $sd
	 * @param type $ed
	 * @return repeats
	 */
	private function _migrate() {
die('XXXXXXXXXXXXXX');
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (admin/util/migrate)"));
			$this->ajax->output();
		}

		$sql = "SELECT		TR.next_due_date, TR.id AS tr_id, T.id
				FROM		transaction T
				LEFT JOIN	transaction_repeat TR ON TR.id = T.transaction_repeat_id AND TR.is_deleted = 0
				LEFT JOIN	transaction_split TS ON TS.transaction_id = T.id
				WHERE		T.transaction_repeat_id IS NOT NULL AND T.is_deleted = 0
				ORDER BY	T.transaction_repeat_id, T.transaction_date ASC
				";
		$transactions = new transaction();
		$transactions->queryAll($sql);
//print $transactions;
//die;
		$last_transaction_repeat_id = false;
		$last_transaction_id = false;
		$last_next_due_date = false;
		foreach ($transactions as $transaction) {
			if ($last_transaction_repeat_id !== false && $transaction->tr_id !== $last_transaction_repeat_id) {
				$tr = new transaction($last_transaction_id);
				$tr->next_due_date = $last_next_due_date;
//print $tr;
//				$tr->save();
			}
			$last_transaction_repeat_id = $transaction->tr_id;
			$last_transaction_id = $transaction->id;
			$last_next_due_date = $transaction->next_due_date;
		}
		$this->ajax->output();
	}

}

// EOF