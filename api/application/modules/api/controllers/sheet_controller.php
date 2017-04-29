<?php
/*
 * REST Budget controller
 */

require_once ('rest_controller.php');

class sheet_controller Extends rest_controller {

	public function __construct() {
		parent::__construct();
	}

	public function index() {
		$this->ajax->addError(new AjaxError("403 - Forbidden (sheet/index)"));
		$this->ajax->output();
	}

	public function loadAll() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (sheet/loadAll)"));
			$this->ajax->output();
		}

		$start_date = $this->input->get('start_date');
		$end_date = $this->input->get('end_date');

		$interval = $this->input->get('interval');
		if (!is_numeric($interval)) {
			$this->ajax->addError(new AjaxError("Invalid interval - sheet/load"));
			$this->ajax->output();
		}

		$accounts = array();

		$start = new DateTime();
		$start_date = explode('T', $start_date);
		$start_date = explode('-', $start_date[0]);
		$start->setdate($start_date[0], $start_date[1], $start_date[2]);

		$end = new DateTime();
		$end_date = explode('T', $end_date);
		$end_date = explode('-', $end_date[0]);
		$end->setdate($end_date[0], $end_date[1], ++$end_date[2]);
		$sd = $start->format('Y-m-d');
		$ed = $end->format('Y-m-d');
$this->ajax->setData('sd', $sd);
$this->ajax->setData('ed', $ed);

		$balance_forward = 0;
		$transaction = new transaction();
		$transaction->select('transaction.id, transaction.transaction_date, transaction.category_id, transaction.vendor_id, transaction.bank_account_id, transaction.amount, transaction.type, transaction.description, transaction.notes, transaction.bank_account_balance, transaction.is_uploaded, transaction.reconciled_date, transaction.check_num');
		$transaction->where('transaction.is_deleted', 0);
		$transaction->where('transaction.transaction_date >= ', $sd);
		$transaction->where('transaction.transaction_date < ', $ed);
		$transaction->orderBy('transaction.transaction_date', 'ASC', FALSE);
		$transaction->orderBy('transaction.id', 'ASC');
		$transaction->result();
		if ($transaction->numRows()) {
			$account_balance = array();
			foreach ($transaction as $tr) {
				isset($tr->vendor);
				if ($tr->splits) {
					foreach ($tr->splits as $split) {
						isset($split->vendor);
						$split->transaction_date	= $tr->transaction_date;
						$split->bank_account_id		= $tr->bank_account_id;
						$split->is_uploaded			= $tr->is_uploaded;
						$split->reconciled_date		= $tr->reconciled_date;
					}
				}
				$tr->transaction_type = 0;		// actual transaction
				if (empty($account_balance[$tr->bank_account_id])) {
					$account_balance[$tr->bank_account_id] = true;
					switch ($tr->type) {
						case 'DEBIT':
						case 'CHECK':
						case 'SALE':
							$balance_forward += ($tr->bank_account_balance + $tr->amount);
							break;
						case 'CREDIT':
						case 'DSLIP':
						case 'RETURN':
						case 'PAYMENT':
							$balance_forward += ($tr->bank_account_balance - $tr->amount);
							break;
					}
				}
				array_push($accounts, $tr->bank_account_id);
			}
		}

		$transactions = array();
		$repeats = $this->loadRepeats($sd, $ed, 1);
		if ($repeats->numRows()) {
			foreach ($repeats as $repeat) {
				isset($repeat->vendor);
				foreach ($repeat->next_due_dates as $next_due_date) {
					$tr = array(
'id'=> $repeat->id,	// TEMPORARY
								'bank_account_id'	=> $repeat->bank_account_id,
								'description'		=> $repeat->description,
								'notes'				=> $repeat->notes,
								'transaction_type'	=> 2);					// Repeat transaction
					if ($repeat->splits) {
						foreach ($repeat->splits as $split) {
							$tr['category_id']		= $split->category_id;
							$tr['bank_account_id']	= $repeat->bank_account_id;
							$tr['vendor']			= $split->vendor;
							$tr['amount']			= $split->amount;
							$tr['type']				= $split->type;
							$tr['transaction_date']	= $next_due_date;
							$transactions[] = $tr;
						}
					} else {
						$tr['category_id']		= $repeat->category_id;
						$tr['bank_account_id']	= $repeat->bank_account_id;
						$tr['vendor']			= $repeat->vendor;
						$tr['amount']			= $repeat->amount;
						$tr['type']				= $repeat->type;
						$tr['transaction_date']	= $next_due_date;
						$transactions[] = $tr;
					}
					array_push($accounts, $repeat->bank_account_id);
				}
			}
		}

		$forecasted = $this->loadForecast($sd, $ed, 1);
		if ($forecasted->numRows()) {
			foreach ($forecasted as $forecast) {
				foreach ($forecast->next_due_dates as $next_due_date) {
					$tr = array(
'id'=> $repeat->id,	// TEMPORARY
								'description'		=> $forecast->description,
								'notes'				=> $forecast->notes,
								'transaction_type'	=> 1,					// Forecast transaction
								'category_id'		=> $forecast->category_id,
								'bank_account_id'	=> $forecast->bank_account_id,
								'amount'			=> $forecast->amount,
								'type'				=> $forecast->type,
								'transaction_date'	=> $next_due_date);
					$transactions[] = $tr;
					array_push($accounts, $forecast->bank_account_id);
				}
			}
		}

		$bank_accounts = new bank_account();
		$bank_accounts->whereNotDeleted();
		$bank_accounts->where('date_opened <', $sd);
		$bank_accounts->groupStart();
		$bank_accounts->orWhere('date_closed IS NULL', null, FALSE);
		$bank_accounts->orWhere('date_closed >', $ed);
		$bank_accounts->groupEnd();
		$bank_accounts->whereNotIn('id', array_unique($accounts));
		$bank_accounts->result();

		$account_balances = array();
		if ($bank_accounts->numRows()) {
			foreach ($bank_accounts as $account) {
				$balance_transaction = new transaction();
				$balance_transaction->whereNotDeleted();
				$balance_transaction->where('transaction_date < ', $sd);
				$balance_transaction->where('bank_account_id', $account->id);
				$balance_transaction->orderBy('transaction_date', 'desc');
				$balance_transaction->orderBy('id', 'desc');
				$balance_transaction->row();
				if ($balance_transaction->numRows()) {
					$tr = array(
								'id'					=> 2157,
								'bank_account_id'		=> $account->id,
								'description'			=> 'BALANCE',
								'transaction_type'		=> 0,					// dummy balance transaction
								'amount'				=> 0,
								'bank_account_balance'	=> $balance_transaction->bank_account_balance,
								'reconciled_date'		=> (!empty($balance_transaction->reconciled_date)) ? $sd: NULL,
								'type'					=> 'DEBIT',
								'transaction_date'		=> $sd);
					$transactions[] = $tr;
					$balance_forward += $balance_transaction->bank_account_balance;
				}
			}
		}

		if (count($transactions) > 0) {
			$transaction = array_merge($transactions, $transaction->toArray());

			usort($transaction, function($a, $b) {
				$diff = strtotime($a['transaction_date']) - strtotime($b['transaction_date']);
				if ($diff < 0) {
					return -1;
				} elseif ($diff > 0) {
					return 1;
				} else {
					return $a['id'] - $b['id'];
				}
			});
		} else {
			$transaction = $transaction->toArray();
		}
				
		$this->ajax->setData('balance_forward', $balance_forward);

		$this->ajax->setData('result', $transaction);

		$this->ajax->output();
	}

}

// EOF