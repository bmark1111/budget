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
			$this->ajax->addError(new AjaxError("403 - Forbidden (admin/util/buildvendors)"));
			$this->ajax->output();
		}

		$vendors = new vendor();
		$vendors->result();
		foreach ($vendors as $vendor) {
			$like = str_replace(' ', '%', $vendor->name);
			$transactions = new transaction();
			$transactions->like('description', $like, 'both', FALSE);
			$transactions->result();
echo $transactions->lastQuery()."\n";
print $transactions;
die;
			$info = array();
			foreach ($transactions as $transaction) {
				if (!$transaction->vendor_id || $transaction->vendor_id == 0) {
					$info[] = array();
				}
			}
			$this->ajax->setData();
//			$sql = "UPDATE transaction SET `vendor_id` = " . $vendor->id . " WHERE `description` LIKE '%" . str_replace("'", "\'", $like) . "%'";
//			$vendor->queryAll($sql);
//echo $sql."\n";
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
							$amount -= $transaction->amount;
							break;
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
				$tr->save();
			}
			$last_transaction_repeat_id = $transaction->tr_id;
			$last_transaction_id = $transaction->id;
			$last_next_due_date = $transaction->next_due_date;
		}
		$this->ajax->output();
	}

}

// EOF