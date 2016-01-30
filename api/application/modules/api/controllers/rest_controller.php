<?php
/*
 * REST controller
 */

class rest_controller Extends EP_Controller {

	protected $debug = TRUE;

	public function __construct() {
		parent::__construct();
//$this->_balanceUpdate();				// resets all bank balances
//die('XXXXXXXXXXXXXXXXXXXXXXXXXXXXx');
		$class = get_class($this);
		if ($class !== 'upload_controller') {
			if ($resetBalances = $this->appdata->get('resetBalances')) {	// get resets
				foreach($resetBalances as $account_id => $date) {
					// for each reset adjust the account balance
					$this->_adjustAccountBalances($date, $account_id);
				}
				$this->appdata->remove('resetBalances');	// remove the reset balances from app data
			}
		}
	}

	private function _balanceUpdate() {
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
	}

	/**
	 * @name _isValidDate
	 * @name {function}
	 * @param {date} $myDateString
	 * @return {bool}
	 */
	private function _isValidDate($myDateString){
		return (bool)strtotime($myDateString);
	}

	/**
	 * 
	 * @name resetAccountBalances
	 * @type {function}
	 */
	public function reconcileTransactions() {
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (rest/reconcileTransactions)"));
			$this->ajax->output();
		}

		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);
		if (!empty($_POST['date']) && $this->_isValidDate($_POST['date']) && !empty($_POST['account_id']) && is_numeric($_POST['account_id'])) {
			$sql = "UPDATE	transaction "
				. "SET		reconciled_date = '" . $_POST['date'] . "' "
				. "WHERE	reconciled_date IS NULL"
				. "		AND is_deleted = 0"
				. "		AND	bank_account_id = " . $_POST['account_id']
				. "		AND	transaction_date <= '" . $_POST['date'] . "'";
			$transaction = new transaction();
			$transaction->queryAll($sql);
		} else {
			$this->ajax->addError(new AjaxError("Invalid reconcile transaction date (rest/reconcileTransactions)"));
		}
		$this->ajax->output();
	}

	/**
	 * @name resetBalances
	 * @param type $resets
	 */
	protected function resetBalances($resets) {
		$update = FALSE;
		$resetBalances = $this->appdata->get('resetBalances');	// get existing resets
		foreach ($resets as $account_id => $date) {
			if (empty($resetBalances[$account_id]) || strtotime($date) < strtotime($resetBalances[$account_id]))
			{	// found a lower date to reset to
				$resetBalances[$account_id] = $date;
				$update = TRUE;
			}
		}
		if ($update) {
			$this->appdata->set('resetBalances', $resetBalances);
		}
	}

	/**
	 * @name resetAccountBalances
	 * @type {function}
	 * @param {date} $original_transaction_date - original transaction date if it exists
	 * @param {date} $new_transaction_date - new transaction date
	 * @return {undefined}
	 */
	private function _adjustAccountBalances($transaction_date, $account_id) {
		if ($this->_isValidDate($transaction_date)) {
			// get the date from which to reset the bank account balance
			$transaction = new transaction();
			$transaction->select('MAX(transaction_date) AS date');
			$transaction->whereNotDeleted();
			$transaction->where("transaction_date < '" . $transaction_date . "'", NULL, FALSE);
			$transaction->where("bank_account_id", $account_id);
			$transaction->limit(1);
			$transaction->row();
			if (!empty($transaction->date)) {
				// now get the transactions that need the balance to be reset
				$transactions = new transaction();
				$transactions->whereNotDeleted();
				$transactions->where("transaction_date >= '" . $transaction->date . "'", NULL, FALSE);
				$transactions->where("bank_account_id", $account_id);
				$transactions->orderBy('transaction_date', 'ASC');
				$transactions->orderBy('id', 'ASC');
				$transactions->result();
				if ($transactions->numRows()) {
					$first = TRUE;
					$bank_account_balances = array();
					foreach ($transactions as $transaction) {
						if (!$first || empty($transaction->bank_account_balance)) {
							switch ($transaction->type) {
								case 'DEBIT':
								case 'CHECK':
									$bank_account_balances[$transaction->bank_account_id] -= $transaction->amount;
									break;
								case 'CREDIT':
								case 'DSLIP':
									$bank_account_balances[$transaction->bank_account_id] += $transaction->amount;
									break;
							}
							$transaction->bank_account_balance = $bank_account_balances[$transaction->bank_account_id];
							$transaction->save();
						} else {
							$bank_account_balances[$transaction->bank_account_id] = $transaction->bank_account_balance;
							$first = FALSE;
						}
					}
				}
			}
		}
	}

	/*
	 * sd = we need the first available balance before this date
	 * bank_account_id = bank account id
	 */
	protected function getBankAccountBalance($sd, $account_id) {
		$transaction = new transaction();
		$transaction->whereNotDeleted();
		$transaction->where("transaction_date < '" . $sd . "'", NULL, FALSE);
		$transaction->where('bank_account_id', $account_id);
		$transaction->orderBy('transaction_date', 'DESC');
		$transaction->limit(1);
		$transaction->row();
		if ($transaction->numRows()) {
			return array($transaction->transaction_date, $transaction->bank_account_balance, $transaction->reconciled_date);
		} else {
			return array(NULL, 0, NULL);
		}
	}
}

// EOF