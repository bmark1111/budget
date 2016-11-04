<?php
/*
 * REST Budget controller
 */

require_once ('rest_controller.php');

class vendorx
{
    public $iddd = 1;
	public $nameeee = 'asdfgh';
}

class sheet_controller Extends rest_controller {

	public function __construct() {
		parent::__construct();
	}

	public function index() {
//		$this->ajax->set_header("Forbidden", '403');
		$this->ajax->addError(new AjaxError("403 - Forbidden (sheet/index)"));
		$this->ajax->output();
	}

	public function loadAll() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
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

		switch ($this->budget_mode) {
			case 'weekly':
			case 'bi-weekly':
				$offset = $this->_getEndDay();
				if ($interval == 0) {
					$start_day = ($offset - ($this->budget_interval * ($this->sheet_views)));					// go back 'sheet views'
					$end_day = ($offset + ($this->budget_interval * ($this->sheet_views + 1)));					// go forward 'sheet views'
				} else if ($interval < 0) {
					$start_day = ($offset - ($this->budget_interval * ($this->sheet_views - $interval)));		// - 'sheet_views' entries and adjust for interval
					$end_day = ($offset - ($this->budget_interval * ($this->sheet_views - $interval - 1)));		// + 'sheet_views' entries and adjust for interval
				} else if ($interval > 0) {
					$start_day = ($offset + ($this->budget_interval * ($this->sheet_views + $interval - 1)));	// - 'sheet_views' entries and adjust for interval
					$end_day = ($offset + ($this->budget_interval * ($this->sheet_views + $interval)));			// + 'sheet_views' entries and adjust for interval
				}
				$sd = date('Y-m-d', strtotime($this->budget_start_date . " +" . $start_day . " Days"));
				$ed = date('Y-m-d', strtotime($this->budget_start_date . " +" . $end_day . " Days"));
//echo "sd = $sd\n";
//echo "ed = $ed\n";
//die;
				break;
			case 'semi-monthy':
				break;
			case 'monthly':
				$start = new DateTime();
				$end = new DateTime();
				if ($interval == 0) {
					$start->modify('first day of this month');
					$end->modify('first day of this month');
					$start_month = $this->budget_interval * ($this->sheet_views - 1);				// go back 'sheet views'
					$start->sub(new DateInterval("P" . $start_month . "M"));
					$end_month = $this->budget_interval * ($this->sheet_views + 1);					// go forward 'sheet views'
					$end->add(new DateInterval("P" . $end_month . "M"));
				} else {
					$start_date = explode('T', $start_date);
					$start_date = explode('-', $start_date[0]);
					$start->setdate($start_date[0], $start_date[1], $start_date[2]);

					$end_date = explode('T', $end_date);
					$end_date = explode('-', $end_date[0]);
					$end->setdate($end_date[0], $end_date[1], ++$end_date[2]);
				}
				$sd = $start->format('Y-m-01');
				$ed = $end->format('Y-m-01');
				break;
			default:
				$this->ajax->addError(new AjaxError("Invalid budget_mode setting (sheet/loadAll)"));
				$this->ajax->output();
		}

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
							$balance_forward += ($tr->bank_account_balance + $tr->amount);
							break;
						case 'CREDIT':
						case 'DSLIP':
							$balance_forward += ($tr->bank_account_balance - $tr->amount);
							break;
					}
				}
			}
		}

		$this->ajax->setData('balance_forward', $balance_forward);

		$transactions = array();
		$repeats = $this->loadRepeats(array(1 => 1, 2 => 2), $sd, $ed, 1);
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
				}
			}
		}

		$forecasted = $this->loadForecast(array(1 => 1, 2 => 2), $sd, $ed, 1);
		if ($forecasted->numRows()) {
			foreach ($forecasted as $forecast) {
				foreach ($forecast->next_due_dates as $next_due_date) {
					$tr = array(
'id'=> $repeat->id,	// TEMPORARY
								'bank_account_id'	=> $forecast->bank_account_id,
								'description'		=> $forecast->description,
								'notes'				=> $forecast->notes,
								'transaction_type'	=> 1,					// Forecast transaction
								'category_id'		=> $forecast->category_id,
								'bank_account_id'	=> $forecast->bank_account_id,
								'amount'			=> $forecast->amount,
								'type'				=> $forecast->type,
								'transaction_date'	=> $next_due_date);
					$transactions[] = $tr;
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

//		// reset the account balances
//		$bank_account_balance = array();
//		$all = FALSE;
//		foreach ($transaction as &$tr) {
//			if ($tr['transaction_type'] !== 0 || $all) {
//				switch ($tr['type']) {
//					case 'DEBIT':
//					case 'CHECK':
//						if (empty($bank_account_balance[$tr['bank_account_id']])) {
//							$bank_account_balance[$tr['bank_account_id']] = 0;
//						}
//						$tr['bank_account_balance'] = $bank_account_balance[$tr['bank_account_id']] - $tr['amount'];
//						break;
//					case 'CREDIT':
//					case 'DSLIP':
//						if (empty($bank_account_balance[$tr['bank_account_id']])) {
//							$bank_account_balance[$tr['bank_account_id']] = 0;
//						}
//						$tr['bank_account_balance'] = $bank_account_balance[$tr['bank_account_id']] + $tr['amount'];
//						break;
//				}
//				$all = TRUE;		// after the first balance adjustment then adjust all balances
//			}
//			$bank_account_balance[$tr['bank_account_id']] = $tr['bank_account_balance'];
//		}
		$this->ajax->setData('result', $transaction);

		$this->ajax->output();
	}

/*	public function load() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (sheet/load)"));
			$this->ajax->output();
		}

		$categories = new category();
		$categories->whereNotDeleted();
		$categories->orderBy('order');
		$categories->result();

		$interval = $this->input->get('interval');
		if (!is_numeric($interval)) {
			$this->ajax->addError(new AjaxError("Invalid interval - sheet/load"));
			$this->ajax->output();
		}

		$select = array();
		$select[] = "T.transaction_date";
		foreach ($categories as $category) {
			$select[] = "SUM(CASE WHEN T.category_id = " . $category->id . " AND (T.type = 'CREDIT' OR T.type = 'DSLIP') THEN T.amount ELSE 0 END)" .
						" + SUM(CASE WHEN TS.category_id = " . $category->id . " AND (TS.type = 'CREDIT' OR TS.type = 'DSLIP') THEN TS.amount ELSE 0 END)" .
						" AS total_" . $category->id . "_credit," .
						" - SUM(CASE WHEN T.category_id = " . $category->id . " AND (T.type = 'CHECK' OR T.type = 'DEBIT') THEN T.amount ELSE 0 END) " .
						" - SUM(CASE WHEN TS.category_id = " . $category->id . " AND (TS.type = 'CHECK' OR TS.type = 'DEBIT') THEN TS.amount ELSE 0 END) " .
						" AS total_" . $category->id . "_debit";
		}

		$sql = array();
		$sql[] = "SELECT";
		$sql[] = implode(',', $select);
		$sql[] = "FROM transaction T";
		$sql[] = "LEFT JOIN transaction_split TS ON TS.transaction_id = T.id AND TS.is_deleted = 0";

		switch ($this->budget_mode) {
			case 'weekly':
			case 'bi-weekly':
				$offset = $this->_getEndDay();
				if ($interval == 0) {
					$start_day = ($offset - ($this->budget_interval * ($this->sheet_views)));					// go back 'sheet views'
					$end_day = ($offset + ($this->budget_interval * ($this->sheet_views)));						// go forward 'sheet views'
				} else if ($interval < 0) {
					$start_day = ($offset - ($this->budget_interval * ($this->sheet_views - $interval)));		// - 'sheet_views' entries and adjust for interval
					$end_day = ($offset - ($this->budget_interval * ($this->sheet_views - $interval - 1)));		// + 'sheet_views' entries and adjust for interval
				} else if ($interval > 0) {
					$start_day = ($offset + ($this->budget_interval * ($this->sheet_views + $interval - 1)));	// - 'sheet_views' entries and adjust for interval
					$end_day = ($offset + ($this->budget_interval * ($this->sheet_views + $interval)));			// + 'sheet_views' entries and adjust for interval
				}
				$sd = date('Y-m-d', strtotime($this->budget_start_date . " +" . $start_day . " Days"));
				$ed = date('Y-m-d', strtotime($this->budget_start_date . " +" . $end_day . " Days"));

				$sql[] = "WHERE T.transaction_date >= '" . $sd . "' AND T.transaction_date < '" . $ed . "' AND T.is_deleted = 0";
				$sql[] = "GROUP BY YEAR(T.transaction_date), MONTH(T.transaction_date), DAYOFYEAR(T.transaction_date)";
				$sql[] = "ORDER BY YEAR(T.transaction_date), MONTH(T.transaction_date), DAYOFYEAR(T.transaction_date) ASC";
				break;
			case 'semi-monthy':
				$sql[] = "WHERE T.transaction_date >= '" . $sd . "' AND T.transaction_date < '" . $ed . "' AND T.is_deleted = 0";
				$sql[] = "GROUP BY YEAR(T.transaction_date), MONTH(T.transaction_date), DAYOFYEAR(T.transaction_date)";
				$sql[] = "ORDER BY YEAR(T.transaction_date), MONTH(T.transaction_date), DAYOFYEAR(T.transaction_date) ASC";
				break;
			case 'monthly':
				$start = new DateTime();
				$end = new DateTime();
				if ($interval == 0) {
					$start_month = $this->budget_interval * ($this->sheet_views - 1);				// go back 'sheet views'
					$start->sub(new DateInterval("P" . $start_month . "M"));
					$end_month = $this->budget_interval * ($this->sheet_views + 1);					// go forward 'sheet views'
					$end->add(new DateInterval("P" . $end_month . "M"));
				} else if ($interval < 0) {
					$start_month = $this->budget_interval * ($this->sheet_views - $interval - 1);	// - 'sheet_views' entries and adjust for interval
					$start->sub(new DateInterval("P" . $start_month . "M"));
					$end_month = $this->budget_interval * ($this->sheet_views - $interval);			// + 'sheet_views' entries and adjust for interval
					$end->add(new DateInterval("P" . $end_month . "M"));
				} else if ($interval > 0) {
					$start_month = $this->budget_interval * ($this->sheet_views + $interval);		// go back 'sheet views' and adjust for interval
					$start->add(new DateInterval("P" . $start_month . "M"));
					$end_month = $this->budget_interval * ($this->sheet_views + $interval + 1);		// go forward 'sheet views' and adjust for interval
					$end->add(new DateInterval("P" . $end_month . "M"));
				}
				$sd = $start->format('Y-m-01');
				$ed = $end->format('Y-m-01');

				$sql[] = "WHERE T.transaction_date >= '" . $sd . "' AND T.transaction_date < '" . $ed . "' AND T.is_deleted = 0";
				$sql[] = "GROUP BY YEAR(T.transaction_date), MONTH(T.transaction_date)";
				$sql[] = "ORDER BY YEAR(T.transaction_date), MONTH(T.transaction_date) ASC";
				break;
			default:
				$this->ajax->addError(new AjaxError("Invalid budget_mode setting (sheet/load)"));
				$this->ajax->output();
		}

		$transactions = new transaction();
		$transactions->queryAll(implode(' ', $sql));

		$running_total = $this->_getBalanceForward($sd);

		// get the accounts
		$accounts = new bank_account();
		$accounts->select("bank_account.id, bank_account.date_opened, bank_account.date_closed, CONCAT(`bank`.`name`,' ',`bank_account`.`name`) as name", FALSE);
		$accounts->join('bank', 'bank.id = bank_account.bank_id');
		$accounts->where('bank_account.is_deleted', 0);
		$accounts->result();

		// get any repeats for this interval
		$repeats = $this->loadRepeats($categories, $sd, $ed, 1);
		$repeats = $this->sumRepeats($repeats, $sd, $ed);

		// get the future forecast
		$forecasted = $this->loadForecast($categories, $sd, $ed, 1);
		$forecast = $this->forecastIntervals($categories, $forecasted, $sd, $ed);

		$data = array();
		$data['balance_forward'] = $running_total;
		$data['interval_total'] = 0;
		$data['running_total'] = $running_total;

		$output = array();
		$date_offset = 0;

		// create interval totals with no values
		$noTotals = array();
		foreach ($categories as $category) {
			$noTotals[$category->id] = NULL;
		}

		if ($transactions->numRows() == 0) {
			$data['totals'] = $noTotals;
			$data['types'] = array();
			$data['interval_total'] = 0;
//			foreach ($forecast[0]['totals'] as $y => $value) {
//				if ($value != 0) {
//					$data['types'][$y] = 1;
//				}
//			}

			$isd = $sd;																					// set the first interval start date
			$ied = $this->_getNextDate($isd, $this->budget_interval, $this->budget_interval_unit);
			$ied = $this->_getNextDate($ied, -1, 'days');												// set the first interval end date
		} else {
			$isd = $sd;																					// set the first interval start date
			$ied = $this->_getNextDate($isd, $this->budget_interval, $this->budget_interval_unit);
			$ied = $this->_getNextDate($ied, -1, 'days');												// set the first interval end date

			// now sort transactions into intervals
			foreach ($transactions as $transaction) {
				while (strtotime($transaction->transaction_date) > strtotime($ied)) {
					// make accounts entry
					foreach ($accounts as $account) {
						$data['accounts'][$account->id] = array('bank_account_id' => $account->id, 'name' => $account->name, 'balance' => NULL);
					}

					$data['interval_beginning']	= date('c', strtotime($isd));
					$data['interval_ending']	= date('c', strtotime($ied . " 23:59:59"));
					if (empty($data['totals'])) {
						// no totals for this interval
						$data['totals'] = $noTotals;
					}
					$data['running_total'] = $running_total;
					$output[] = $data;

					$data = array();
					$data['interval_total'] = 0;
					$data['running_total'] = $running_total;
					$data['balance_forward'] = $running_total;

					$isd = $this->_getNextDate($isd, $this->budget_interval, $this->budget_interval_unit);		// set the interval start date
					$ied = $this->_getNextDate($isd, $this->budget_interval, $this->budget_interval_unit);
					$ied = $this->_getNextDate($ied, -1, 'days');
				}

				foreach ($transaction as $label => $value) {
					if (substr($label, 0, 6) == 'total_') {
						$index = explode('_', $label);
						if (!isset($data['totals'][$index[1]])) {
							$data['totals'][$index[1]] = NULL;
						}
						if ($value <> 0) {
							$data['amounts'][$index[1]] = 1;
							if (isset($data['totals'][$index[1]])) {
								$data['totals'][$index[1]] += $value;
							} else {
								$data['totals'][$index[1]] = $value;
							}
							$data['types'][$index[1]] = 0;
							$data['interval_total'] += $value;
							$running_total += $value;
						}
					}
				}
			}
		}

		$data['running_total']		= $running_total;
		$data['interval_beginning']	= date('c', strtotime($isd));
		$data['interval_ending']	= date('c', strtotime($ied . " 23:59:59"));
		// make accounts entry
		foreach ($accounts as $account) {
			$data['accounts'][$account->id] = array('bank_account_id' => $account->id, 'name' => $account->name, 'balance' => NULL);
		}
		$output[] = $data;

		if ($interval === "0") {
			// show budget views * 2 intervals in the initial load
			while (count($output) < (($this->sheet_views * 2))) {		// show sheet_views before current + current + ($budget->views-1) after current
				foreach ($data['totals'] as &$total) {
					$total = NULL;
				}

				$isd = $this->_getNextDate($isd, $this->budget_interval, $this->budget_interval_unit);		// set the interval start date
				$ied = $this->_getNextDate($isd, $this->budget_interval, $this->budget_interval_unit);
				$ied = $this->_getNextDate($ied, -1, 'days');												// set the interval end date
				$data['running_total']		= $running_total;
				$data['interval_beginning']	= date('c', strtotime($isd));
				$data['interval_ending']	= date('c', strtotime($ied . " 23:59:59"));
				$data['interval_total']		= 0;
				if (empty($data['running_total']) || $data['running_total'] == 0) {
					$data['running_total'] = $running_total;
				}
				// make accounts entry
				foreach ($accounts as $account) {
					$data['accounts'][$account->id] = array('bank_account_id' => $account->id, 'name' => $account->name, 'balance' => NULL);
				}
				$output[] = $data;
			}
		}

		$adjustments = array();
		$balance_forward = FALSE;
		$running_total = 0;

		// now add the forecast & repeats to relevant intervals
		for ($x = 0; $x < count($output); $x++) {
			$start_date = strtotime($output[$x]['interval_beginning']);
			$end_date = strtotime($output[$x]['interval_ending']);
			$now = time();
			// only add forecast & repeats from current interval through future intervals
			if (($now >= $start_date && $now <= $end_date) || $now < $end_date) {
				if ($balance_forward) {
					$output[$x]['balance_forward'] = $balance_forward;
				}

				$totals = array();
				$types = array();
				// check to see what current values need to be from the forecast or from the repeats
				foreach ($output[$x]['totals'] as $y => $categoryTotal) {
					$category_total = NULL;
					$type = 0;
					// check the forecast
					if (isset($forecast[$x]['totals'][$y])) {
						// if forecast has a value then ... add the forecasted amount into totals
						$type			+= ($categoryTotal === NULL) ? '1': '2';		// total type, 1 = just forecast amount, 2 = forecast and actual amounts
						$category_total	+= floatval($forecast[$x]['totals'][$y]);		// update the category total with the forecast amount
						$running_total	+= floatval($forecast[$x]['totals'][$y]);		// update the running total with the forecast amount
						foreach ($accounts as $account) {
							if (!empty($forecast[$x]['adjustments'][$y][$account->id])) {
								if (empty($adjustments[$account->id])) {
									$adjustments[$account->id] = floatval($forecast[$x]['adjustments'][$y][$account->id]);
								} else {
									$adjustments[$account->id] += floatval($forecast[$x]['adjustments'][$y][$account->id]);
								}
							}
						}
					}
					// check the repeats
					if (isset($repeats[$x]['totals'][$y])) {
						// if repeats has a value then ... add the repeat amount into totals
						$type			+= 10;											// total type, includes a repeat amount
						$category_total	+= floatval($repeats[$x]['totals'][$y]);		// update the category total with the repeat amount
						$running_total	+= floatval($repeats[$x]['totals'][$y]);		// update the running total with the repeat amount
					}
					if ($categoryTotal !== NULL || $category_total !== NULL) {
						$totals[$y] = floatval($categoryTotal) + $category_total;		// set the category total
					} else {
						$totals[$y] = NULL;
					}
					$types[$y] = $type;													// set the amount type
					$output[$x]['interval_total'] += $category_total;					// update the interval total
				}
				// adjust the account totals for the repeats
				foreach ($accounts as $account) {
					if (!empty($repeats[$x]['adjustments'][$account->id])) {
						if (empty($adjustments[$account->id])) {
							$adjustments[$account->id] = floatval($repeats[$x]['adjustments'][$account->id]);
						} else {
							$adjustments[$account->id] += floatval($repeats[$x]['adjustments'][$account->id]);
						}
					}
				}
				$output[$x]['totals']		= $totals;
				$output[$x]['types']		= $types;
				$output[$x]['adjustments']	= $adjustments;
				if (empty($output[$x]['running_total'])) {
					$output[$x]['running_total'] = $running_total;
				} else {
					$output[$x]['running_total'] += $running_total;
				}
				$balance_forward = $output[$x]['running_total'];
			}
		}

		// get the current account balances
		$balances = $this->_balances($sd, $ed);

		// now put the bank balances in for each interval
		foreach ($output as $x => $intervalx) {
			// find the latest balance for this interval
			foreach ($balances as $balance) {
				if (strtotime($balance->transaction_date) < strtotime($intervalx['interval_beginning'])
								||
					strtotime($balance->transaction_date) > strtotime($intervalx['interval_ending'])) {
					continue;
				}
				if (strtotime($balance->transaction_date) >= strtotime($intervalx['interval_beginning'])
						&& strtotime($balance->transaction_date) <= strtotime(substr($intervalx['interval_ending'],0,10))) {
					// if the transaction falls inside the interval then ....
					// .... get the latest dated account balance for the interval - earlier balances will be overwritten with the latest
					$output[$x]['balances'][$balance->bank_account_id] = $balance->bank_account_balance;	// save the unadjusted account balance
					$output[$x]['accounts'][$balance->bank_account_id]['balance'] = $balance->bank_account_balance;
					$output[$x]['accounts'][$balance->bank_account_id]['balance_date'] = $balance->transaction_date;
					$output[$x]['accounts'][$balance->bank_account_id]['date_opened'] = $balance->date_opened;
					$output[$x]['accounts'][$balance->bank_account_id]['date_closed'] = $balance->date_closed;
					$output[$x]['accounts'][$balance->bank_account_id]['xxxxx'] = 1;
					if (!empty($balance->reconciled_date)
							&&
						(empty($output[$x]['accounts'][$balance->bank_account_id]['reconciled_date'])
								||
						strtotime($balance->reconciled_date) > strtotime($output[$x]['accounts'][$balance->bank_account_id]['reconciled_date']))) {
						$output[$x]['accounts'][$balance->bank_account_id]['reconciled_date'] = $balance->reconciled_date;
					} else {
						$output[$x]['accounts'][$balance->bank_account_id]['reconciled_date'] = $balance->reconciled_date;
					}
				} elseif (empty($output[$x]['accounts'][$balance->bank_account_id]['balance'])) {
					if ($x > 0) {
						// if the balance is not set then get from last interval
						$output[$x]['balances'][$balance->bank_account_id] = $output[$x-1]['balances'][$balance->bank_account_id];	// save the unadjusted account balance
						$output[$x]['accounts'][$balance->bank_account_id]['balance'] = $output[$x-1]['accounts'][$balance->bank_account_id]['balance'];
						$output[$x]['accounts'][$balance->bank_account_id]['balance_date'] = $output[$x-1]['accounts'][$balance->bank_account_id]['balance_date'];
						$output[$x]['accounts'][$balance->bank_account_id]['reconciled_date'] = $output[$x-1]['accounts'][$balance->bank_account_id]['reconciled_date'];
						$output[$x]['accounts'][$balance->bank_account_id]['date_opened'] = $output[$x-1]['accounts'][$balance->bank_account_id]['date_opened'];
						$output[$x]['accounts'][$balance->bank_account_id]['date_closed'] = $output[$x-1]['accounts'][$balance->bank_account_id]['date_closed'];
						$output[$x]['accounts'][$balance->bank_account_id]['xxxxx'] = 2;
					} else {
						// this is first interval, get the last available balance for this account
						$accountBalance = $this->getBankAccountBalance($sd, $balance->bank_account_id);
						$output[$x]['balances'][$balance->bank_account_id] = $accountBalance[1];	// save the unadjusted account balance
						$output[$x]['accounts'][$balance->bank_account_id]['balance_date'] = $accountBalance[0];
						$output[$x]['accounts'][$balance->bank_account_id]['balance'] = $accountBalance[1];
						$output[$x]['accounts'][$balance->bank_account_id]['reconciled_date'] = $accountBalance[2];
						$output[$x]['accounts'][$balance->bank_account_id]['date_opened'] = $accountBalance[3];
						$output[$x]['accounts'][$balance->bank_account_id]['date_closed'] = $accountBalance[4];
						$output[$x]['accounts'][$balance->bank_account_id]['xxxxx'] = 3;
					}
				}
			}

			// now adjust the bank accounts with the forecasted adjustments
			foreach ($output[$x]['accounts'] as $bank_account_id => $account) {
				// make sure we have a balance
				if (empty($account['balance'])) {
					if ($x > 0) {
						// if the balance is not set then get from last interval
						$output[$x]['balances'][$bank_account_id] = $output[$x-1]['balances'][$bank_account_id];	// save the unadjusted account balance
						$output[$x]['accounts'][$bank_account_id]['balance'] = $output[$x-1]['accounts'][$bank_account_id]['balance'];
						$output[$x]['accounts'][$bank_account_id]['balance_date'] = $output[$x-1]['accounts'][$bank_account_id]['balance_date'];
						$output[$x]['accounts'][$bank_account_id]['reconciled_date'] = $output[$x-1]['accounts'][$bank_account_id]['reconciled_date'];
						$output[$x]['accounts'][$bank_account_id]['date_opened'] = $output[$x-1]['accounts'][$bank_account_id]['date_opened'];
						$output[$x]['accounts'][$bank_account_id]['date_closed'] = $output[$x-1]['accounts'][$bank_account_id]['date_closed'];
						$output[$x]['accounts'][$bank_account_id]['xxxxx'] = 4;
					} else {
						$accountBalance = $this->getBankAccountBalance(date('Y-m-d', strtotime($intervalx['interval_beginning'])), $bank_account_id);
						$output[$x]['balances'][$bank_account_id] = $accountBalance[1];								// save the unadjusted account balance
						$output[$x]['accounts'][$bank_account_id]['balance_date'] = $accountBalance[0];
						$output[$x]['accounts'][$bank_account_id]['balance'] = $accountBalance[1];
						$output[$x]['accounts'][$bank_account_id]['reconciled_date'] = $accountBalance[2];
						$output[$x]['accounts'][$bank_account_id]['date_opened'] = $accountBalance[3];
						$output[$x]['accounts'][$bank_account_id]['date_closed'] = $accountBalance[4];
						$output[$x]['accounts'][$bank_account_id]['xxxxx'] = 5;
					}
				}
				$isd = strtotime($intervalx['interval_beginning']);
				$ied = strtotime($intervalx['interval_ending']);
				$now = time();
				// only add forecast adjustment to current and future intervals
				if (($now >= $isd && $now <= $ied) || $now < $ied) {
					if (!empty($output[$x]['adjustments'][$bank_account_id])) {
						// adjust the account balance
						if (!empty($output[$x]['balances'][$bank_account_id])) {
							$output[$x]['accounts'][$bank_account_id]['balance'] = $output[$x]['balances'][$bank_account_id] + $output[$x]['adjustments'][$bank_account_id];
						} else {
							$output[$x]['accounts'][$bank_account_id]['balance'] = $output[$x]['adjustments'][$bank_account_id];
						}
					}
				}
			}
		}

		// now put the bank balances in for each interval
		$y = 0;
		foreach ($output as $x => $intervalx) {
			$output[$x]['accounts'] = array_values(array_filter($output[$x]['accounts']));	// compact the accounts array
		}

		$this->ajax->setData('result', $output);

		$this->ajax->output();
	}
*/
	private function _getBalanceForward($sd) {
		// now calculate the balance brought forward
		$balance = new transaction();
//		$balance->join('transaction_split', 'transaction_split.transaction_id = transaction.id AND transaction_split.is_deleted = 0', 'LEFT');
//		$balance->select("SUM(CASE WHEN transaction.type = 'CREDIT' AND transaction.category_id IS NOT NULL THEN transaction.amount ELSE 0 END) " .
//						" + SUM(CASE WHEN transaction.type = 'DSLIP' AND transaction.category_id IS NOT NULL THEN transaction.amount ELSE 0 END) " .
//						" - SUM(CASE WHEN transaction.type = 'DEBIT' AND transaction.category_id IS NOT NULL THEN transaction.amount ELSE 0 END) " .
//						" - SUM(CASE WHEN transaction.type = 'CHECK' AND transaction.category_id IS NOT NULL THEN transaction.amount ELSE 0 END) " .
//						" + SUM(CASE WHEN transaction_split.type = 'CREDIT' AND transaction.category_id IS NULL THEN transaction_split.amount ELSE 0 END) " .
//						" + SUM(CASE WHEN transaction_split.type = 'DSLIP' AND transaction.category_id IS NULL THEN transaction_split.amount ELSE 0 END) " .
//						" - SUM(CASE WHEN transaction_split.type = 'DEBIT' AND transaction.category_id IS NULL THEN transaction_split.amount ELSE 0 END) " .
//						" - SUM(CASE WHEN transaction_split.type = 'CHECK' AND transaction.category_id IS NULL THEN transaction_split.amount ELSE 0 END) " .
//						" AS balance_forward");
//		$balance->where('transaction.is_deleted', 0);
//		$balance->where("transaction.transaction_date < '" . $sd . "'");
		$balance->select("SUM(CASE WHEN (transaction.type = 'CREDIT' || transaction.type = 'DSLIP') THEN transaction.amount ELSE 0 END) " .
						" - SUM(CASE WHEN (transaction.type = 'DEBIT' || transaction.type = 'CHECK') THEN transaction.amount ELSE 0 END) " .
						" AS balance_forward");
		$balance->whereNotDeleted();
		$balance->where("transaction_date < '" . $sd . "'");
		$balance->row();
/*
 * query took about 0.0010 secs
SELECT 

(SELECT bank_account_balance FROM `transaction` 
WHERE bank_account_id = 1 and transaction_date < '2016-02-01' and is_deleted=0 order by transaction_date desc limit 1) as x1,

(SELECT bank_account_balance FROM `transaction` 
WHERE bank_account_id = 2 and transaction_date < '2016-02-01' and is_deleted=0 order by transaction_date desc limit 1) as x2,

(SELECT bank_account_balance FROM `transaction` 
WHERE bank_account_id = 6 and transaction_date < '2016-02-01' and is_deleted=0 order by transaction_date desc limit 1) as x3
 */
		return $balance->balance_forward;
	}

	private function _balances($sd, $ed) {
		$bank_account_balances = new transaction();
		$bank_account_balances->select('transaction.bank_account_id, transaction.bank_account_balance, transaction.transaction_date, transaction.reconciled_date, bank_account.name, bank_account.date_opened, bank_account.date_closed');
		$bank_account_balances->join('bank_account', 'bank_account.id = transaction.bank_account_id');
		$bank_account_balances->where('transaction.transaction_date >= ', $sd);
		$bank_account_balances->where('transaction.transaction_date < ', $ed);
		$bank_account_balances->groupStart();
		$bank_account_balances->orWhere('bank_account.date_closed IS NULL', FALSE, FALSE);
		$bank_account_balances->orWhere('bank_account.date_closed >= ', $sd);
		$bank_account_balances->groupEnd();
		$bank_account_balances->groupStart();
		$bank_account_balances->orWhere('bank_account.date_closed IS NULL', FALSE, FALSE);
		$bank_account_balances->orWhere('bank_account.date_closed < ', $ed);
		$bank_account_balances->groupEnd();
		$bank_account_balances->where('transaction.is_deleted', 0);
		$bank_account_balances->orderBy('transaction.transaction_date', 'ASC');
		$bank_account_balances->orderBy('transaction.id', 'ASC');
		$bank_account_balances->result();
		return $bank_account_balances;
	}

	private function _getNextDate($myDateTimeISO, $addThese, $unit) {
		$myDateTime = new DateTime($myDateTimeISO);
		$myDayOfMonth = date_format($myDateTime, 'j');
		date_modify($myDateTime, "+" . $addThese . " " . $unit);

		//Find out if the day-of-month has dropped
		$myNewDayOfMonth = date_format($myDateTime, 'j');
		if ($myDayOfMonth > 28 && $myNewDayOfMonth < 4) {
			//If so, fix by going back the number of days that have spilled over
			date_modify($myDateTime, "-" . $myNewDayOfMonth . " " . $unit);
		}
		return date_format($myDateTime, "Y-m-d");
	}

	private function _getEndDay() {
		$xx =  time();
		$yy = intval(strtotime($this->budget_start_date));
		$xx = ($xx - $yy) / (24 * 60 * 60);
		$xx = intval($xx / $this->budget_interval);
		return ($xx * $this->budget_interval);
	}

}

// EOF