<?php
/*
 * REST Budget controller
 */

require_once ('rest_controller.php');

class budget_controller Extends rest_controller {

	protected $debug = TRUE;

	private $budget_interval = FALSE;
	private $budget_interval_unit = FALSE;

	public function __construct() {
		parent::__construct();

		$settings = new setting();
		$settings->result();
		foreach ($settings as $setting) {
			$this->{$setting->name}  = $setting->value;
		}

		switch ($this->budget_mode) {
			case 'weekly':
				$this->budget_interval = 7;
				$this->budget_interval_unit = 'Days';
				break;
			case 'bi-weekly':
				$this->budget_interval = 14;
				$this->budget_interval_unit = 'Days';
				break;
			case 'semi-monthy':
				$this->budget_interval = 1;
				$this->budget_interval_unit = 'Months';
				break;
			case 'monthly':
				$this->budget_interval = 1;
				$this->budget_interval_unit = 'Months';
				break;
			default:
				$this->ajax->addError(new AjaxError("Invalid budget_mode setting (budget controller)"));
				$this->ajax->output();
		}
	}

	public function index() {
//		$this->ajax->set_header("Forbidden", '403');
		$this->ajax->addError(new AjaxError("403 - Forbidden (budget/index)"));
		$this->ajax->output();
	}

	public function load() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (budget/load)"));
			$this->ajax->output();
		}

		$categories = new category();
		$categories->whereNotDeleted();
		$categories->orderBy('order');
		$categories->result();

		$interval = $this->input->get('interval');
		if (!is_numeric($interval)) {
			$this->ajax->addError(new AjaxError("Invalid interval - budget/load"));
			$this->ajax->output();
		}

		$select = array();
		$select[] = "T.transaction_date";
		foreach ($categories as $category) {
			$select[] = "SUM(CASE WHEN T.category_id = " . $category->id . " AND T.type = 'CREDIT' THEN T.amount ELSE 0 END)" .
						" + SUM(CASE WHEN T.category_id = " . $category->id . " AND T.type = 'DSLIP' THEN T.amount ELSE 0 END)" .
						" + SUM(CASE WHEN TS.category_id = " . $category->id . " AND TS.type = 'CREDIT' THEN TS.amount ELSE 0 END)" .
						" + SUM(CASE WHEN TS.category_id = " . $category->id . " AND TS.type = 'DSLIP' THEN TS.amount ELSE 0 END)" .
						"AS total_" . $category->id . "_credit," .
						" - SUM(CASE WHEN T.category_id = " . $category->id . " AND T.type = 'CHECK' THEN T.amount ELSE 0 END)" .
						" - SUM(CASE WHEN T.category_id = " . $category->id . " AND T.type = 'DEBIT' THEN T.amount ELSE 0 END) " .
						" - SUM(CASE WHEN TS.category_id = " . $category->id . " AND TS.type = 'CHECK' THEN TS.amount ELSE 0 END)" .
						" - SUM(CASE WHEN TS.category_id = " . $category->id . " AND TS.type = 'DEBIT' THEN TS.amount ELSE 0 END) " .
						"AS total_" . $category->id . "_debit";
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
					$start_day = ($offset - ($this->budget_interval * ($this->budget_views)));					// go back 'budget views'
					$end_day = ($offset + ($this->budget_interval * ($this->budget_views)));					// go forward 'budget views'
				} else if ($interval < 0) {
					$start_day = ($offset - ($this->budget_interval * ($this->budget_views - $interval)));		// - 'budget_views' entries and adjust for interval
					$end_day = ($offset - ($this->budget_interval * ($this->budget_views - $interval - 1)));	// + 'budget_views' entries and adjust for interval
				} else if ($interval > 0) {
					$start_day = ($offset + ($this->budget_interval * ($this->budget_views + $interval - 1)));	// - 'budget_views' entries and adjust for interval
					$end_day = ($offset + ($this->budget_interval * ($this->budget_views + $interval)));		// + 'budget_views' entries and adjust for interval
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
				$offset = date('n');			// get the current month
				$start = new DateTime();
				$end = new DateTime();
				if ($interval == 0) {
					$start_month = ($offset - ($this->budget_interval * ($this->budget_views + 1)));				// go back 'budget views'
					$end_month = ($offset + ($this->budget_interval * ($this->budget_views - 1)));					// go forward 'budget views'
				} else if ($interval < 0) {
					$start_month = ($offset - ($this->budget_interval * ($this->budget_views - $interval + 1)));	// - 'budget_views' entries and adjust for interval
					$end_month = ($offset - ($this->budget_interval * ($this->budget_views - $interval)));			// + 'budget_views' entries and adjust for interval
				} else if ($interval > 0) {
					$start_month = ($offset + ($this->budget_interval * ($this->budget_views + $interval - 2)));	// go back 'budget views' and adjust for interval
					$end_month = ($offset + ($this->budget_interval * ($this->budget_views + $interval - 1)));		// go forward 'budget views' and adjust for interval
				}
				if ($start_month > 0) {
					$start->add(new DateInterval("P" . $start_month . "M"));
				} else {
					$start->sub(new DateInterval("P" . -$start_month . "M"));
				}

				if ($end_month > 0) {
					$end->add(new DateInterval("P" . $end_month . "M"));
				} else {
					$end->sub(new DateInterval("P" . -$end_month . "M"));
				}

				$sd = $start->format('Y-m-01');
				$ed = $end->format('Y-m-01');

				$sql[] = "WHERE T.transaction_date >= '" . $sd . "' AND T.transaction_date < '" . $ed . "' AND T.is_deleted = 0";
				$sql[] = "GROUP BY YEAR(T.transaction_date), MONTH(T.transaction_date)";
				$sql[] = "ORDER BY YEAR(T.transaction_date), MONTH(T.transaction_date) ASC";
				break;
			default:
				$this->ajax->addError(new AjaxError("Invalid budget_mode setting (budget/load)"));
				$this->ajax->output();
		}

		$transactions = new transaction();
		$transactions->queryAll(implode(' ', $sql));

		$running_total = $this->_getBalanceForward($sd);

		// get the accounts
		$accounts = new bank_account();
		$accounts->select("bank_account.id, CONCAT(`bank`.`name`,' ',`bank_account`.`name`) as name", FALSE);
		$accounts->join('bank', 'bank.id = bank_account.bank_id');
		$accounts->where('bank_account.is_deleted', 0);
		$accounts->result();

		// get the forecast
		$forecasted = $this->_loadForecast($categories, $sd, $ed);

		// now sum the expenses for the forecast intervals
		$offset = 0;
		$forecast = array();
		$xx = 0;
		while (strtotime($sd . ' +' . $offset . ' ' . $this->budget_interval_unit) < strtotime($ed)) {
			$interval_beginning = date('Y-m-d', strtotime($sd . ' +' . $offset . ' ' . $this->budget_interval_unit));
			$interval_ending = date('Y-m-d', strtotime($sd . ' +' . ($offset + $this->budget_interval) . ' ' . $this->budget_interval_unit));
			$interval_ending = date('Y-m-d', strtotime($interval_ending . ' -1 Day'));

			$data = $this->_getForecastByCategory($categories, $forecasted, $interval_beginning);

			$forecast[$xx]['totals']				= $data['totals'];			// load the category totals
			$forecast[$xx]['adjustments']			= $data['adjustments'];		// load the bank account balance adjustments
			$forecast[$xx]['interval_total']		= (!empty($data['interval_total'])) ? $data['interval_total']: 0;	// load the interval total
			$forecast[$xx]['interval_beginning']	= date('c', strtotime($interval_beginning));
			$forecast[$xx]['interval_ending']		= date('c', strtotime($interval_ending . ' 23:59:59'));
			$forecast[$xx]['forecast']				= 1;						// mark this interval as a forecast
			$xx++;
			$offset += $this->budget_interval;
		}

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
			foreach ($forecast[0]['totals'] as $y => $value) {
				if ($value != 0) {
					$data['types'][$y] = '1';
				}
			}

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
			while (count($output) < (($this->budget_views * 2))) {		// show budget->views before current + current + ($budget->views-1) after current
				foreach ($data['totals'] as &$total) {
					$total = NULL;//0;
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

/*		$adjustments = array();
		$balance_forward = FALSE;
		$running_total = 0;

		// now add the forecast to relevant intervals
		foreach ($output as $x => &$interval) {
			$start_date = strtotime($interval['interval_beginning']);
			$end_date = strtotime($interval['interval_ending']);
			$now = time();
			// only add forecast from current interval through future intervals
			if (($now >= $start_date && $now <= $end_date) || $now < $end_date) {
				if ($balance_forward) {
					$interval['balance_forward'] = $balance_forward;
				}
				// check to see what current values need to be from the forecast
				foreach ($interval['totals'] as $y => $intervalAmount) {
//					if ($intervalAmount === NULL && floatval($forecast[$x]['totals'][$y]) !== (float)0) {
					if ($intervalAmount === NULL && $forecast[$x]['totals'][$y] !== NULL) {

						// if interval amount is not set and the forecast has a value then ... use the forecasted amount
						$interval['totals'][$y] = floatval($forecast[$x]['totals'][$y]);			// use the forcasted amount
						$interval['types'][$y] = '1';												// flag this as a forecast total
						$interval['interval_total'] += floatval($forecast[$x]['totals'][$y]);		// update the interval total
						$running_total += floatval($forecast[$x]['totals'][$y]);					// update the running total
//					} elseif (floatval($forecast[$x]['totals'][$y]) !== (float)0) {
					} else {
						// we are not using the forecasted amount so deduct it from the forecasted account balance adjustment
						// need to set the adjustment amount to zero
						if (!empty($forecast[$x]['adjustments'][$y])) {
							foreach ($forecast[$x]['adjustments'][$y] as $bank_account_id => $bank_account_balance) {
								unset($forecast[$x]['adjustments'][$y][$bank_account_id]);
							}
						}
					}
				}

				$interval['adjustments'] = $forecast[$x]['adjustments'];
				if (empty($interval['running_total'])) {
					$interval['running_total'] = $running_total;
				} else {
					$interval['running_total'] += $running_total;
				}
				$balance_forward = $interval['running_total'];

				if (!empty($interval['adjustments'])) {
					foreach ($interval['adjustments'] as $account) {
						foreach ($account as $bank_account_id => $amount) {
							if (empty($adjustments[$bank_account_id])) {
								$adjustments[$bank_account_id] = $amount;
							} else {
								$adjustments[$bank_account_id] += $amount;
							}
						}
					}
					$interval['adjustments'] = $adjustments;
				}
			}
		}
*/
		// get the current account balances
		$balances = $this->_balances($sd, $ed);

		// now put the bank balances in for each interval
		foreach ($output as $x => $intervalx) {
			// find the latest balance for this interval
			foreach ($balances as $balance) {
				if (strtotime($balance->transaction_date) >= strtotime($intervalx['interval_beginning'])
						&& strtotime($balance->transaction_date) <= strtotime(substr($intervalx['interval_ending'],0,10))) {
					// if the transaction falls inside the interval then ....
					// .... get the latest dated account balance for the interval - earlier balances will be overwritten with the latest
					$output[$x]['balances'][$balance->bank_account_id] = $balance->bank_account_balance;	// save the unadjusted account balance
					$output[$x]['accounts'][$balance->bank_account_id]['balance'] = $balance->bank_account_balance;
					$output[$x]['accounts'][$balance->bank_account_id]['balance_date'] = $balance->transaction_date;
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
						$output[$x]['accounts'][$balance->bank_account_id]['xxxxx'] = 2;
					} else {
						// this is first interval, get the last available balance for this account
						$accountBalance = $this->getBankAccountBalance($sd, $balance->bank_account_id);
						$output[$x]['balances'][$balance->bank_account_id] = $accountBalance[1];	// save the unadjusted account balance
						$output[$x]['accounts'][$balance->bank_account_id]['balance_date'] = $accountBalance[0];
						$output[$x]['accounts'][$balance->bank_account_id]['balance'] = $accountBalance[1];
						$output[$x]['accounts'][$balance->bank_account_id]['reconciled_date'] = $accountBalance[2];
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
						$output[$x]['accounts'][$bank_account_id]['xxxxx'] = 4;
					} else {
						$accountBalance = $this->getBankAccountBalance(date('Y-m-d', strtotime($intervalx['interval_beginning'])), $bank_account_id);
						$output[$x]['balances'][$bank_account_id] = $accountBalance[1];								// save the unadjusted account balance
						$output[$x]['accounts'][$bank_account_id]['balance_date'] = $accountBalance[0];
						$output[$x]['accounts'][$bank_account_id]['balance'] = $accountBalance[1];
						$output[$x]['accounts'][$bank_account_id]['reconciled_date'] = $accountBalance[2];
						$output[$x]['accounts'][$bank_account_id]['xxxxx'] = 5;
					}
				}
/*
				// only add forecast adjustment to current and future intervals
				if (($now >= $sd && $now <= $ed) || $now < $ed) {
					if (!empty($output[$x]['adjustments'][$bank_account_id])) {
						// adjust the account balance
						if (!empty($output[$x]['balances'][$bank_account_id])) {
							$output[$x]['accounts'][$bank_account_id]['balance'] = $output[$x]['balances'][$bank_account_id] + $output[$x]['adjustments'][$bank_account_id];
						} else {
							$output[$x]['accounts'][$bank_account_id]['balance'] = $output[$x]['adjustments'][$bank_account_id];
						}
					}
				}
*/
			}
		}

		// now put the bank balances in for each interval
		$_output = array();
		$y = 0;
		foreach ($output as $x => $intervalx) {
			$output[$x]['accounts'] = array_values(array_filter($output[$x]['accounts']));	// compact the accounts array
			$_output[$y++] = $forecast[$x];
			$_output[$y++] = $output[$x];
		}

		$this->ajax->setData('result', $_output);

		$this->ajax->output();
	}

	private function _getBalanceForward($sd) {
		// now calculate the balance brought forward
		$balance = new transaction();
		$balance->join('transaction_split', 'transaction_split.transaction_id = transaction.id AND transaction_split.is_deleted = 0', 'LEFT');
		$balance->select("SUM(CASE WHEN transaction.type = 'CREDIT' AND transaction.category_id IS NOT NULL THEN transaction.amount ELSE 0 END) " .
							" + SUM(CASE WHEN transaction.type = 'DSLIP' AND transaction.category_id IS NOT NULL THEN transaction.amount ELSE 0 END) " .
							" - SUM(CASE WHEN transaction.type = 'DEBIT' AND transaction.category_id IS NOT NULL THEN transaction.amount ELSE 0 END) " .
							" - SUM(CASE WHEN transaction.type = 'CHECK' AND transaction.category_id IS NOT NULL THEN transaction.amount ELSE 0 END) " .
							" + SUM(CASE WHEN transaction_split.type = 'CREDIT' AND transaction.category_id IS NULL THEN transaction_split.amount ELSE 0 END) " .
							" + SUM(CASE WHEN transaction_split.type = 'DSLIP' AND transaction.category_id IS NULL THEN transaction_split.amount ELSE 0 END) " .
							" - SUM(CASE WHEN transaction_split.type = 'DEBIT' AND transaction.category_id IS NULL THEN transaction_split.amount ELSE 0 END) " .
							" - SUM(CASE WHEN transaction_split.type = 'CHECK' AND transaction.category_id IS NULL THEN transaction_split.amount ELSE 0 END) " .
							" AS balance_forward");
		$balance->where('transaction.is_deleted', 0);
		$balance->where("transaction.transaction_date < '" . $sd . "'");
		$balance->row();

		return $balance->balance_forward;
	}

	private function _balances($sd, $ed) {
		$bank_account_balances = new transaction();
		$bank_account_balances->select('transaction.bank_account_id, transaction.bank_account_balance, transaction.transaction_date, transaction.reconciled_date, bank_account.name');
		$bank_account_balances->join('bank_account', 'bank_account.id = transaction.bank_account_id');
		$bank_account_balances->where('transaction.transaction_date >= ', $sd);
		$bank_account_balances->where('transaction.transaction_date < ', $ed);
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

	public function these() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (budget/these)"));
			$this->ajax->output();
		}

		$interval_beginning	= $this->input->get('interval_beginning');
		if (!$interval_beginning || !strtotime($interval_beginning)) {
			$this->ajax->addError(new AjaxError("Invalid interval_beginning - budget/these"));
			$this->ajax->output();
		}
		$interval_beginning = explode('T', $interval_beginning);
		$sd = date('Y-m-d', strtotime($interval_beginning[0]));

		$interval_ending	= $this->input->get('interval_ending');
		if (!$interval_ending || !strtotime($interval_ending)) {
			$this->ajax->addError(new AjaxError("Invalid interval ending - budget/these"));
			$this->ajax->output();
		}
		$interval_ending = explode('T', $interval_ending);
		$ed = date('Y-m-d', strtotime($interval_ending[0]));

		$category_id	= $this->input->get('category_id');
		if ($category_id == 0 || !is_numeric($category_id)) {
			$this->ajax->addError(new AjaxError("Invalid category id - budget/these"));
			$this->ajax->output();
		}

		$transactions = new transaction();
		$sql = "(SELECT T.id, T.transaction_date, T.type, T.description, T.is_uploaded, T.reconciled_date, T.notes, T.amount, A.name AS accountName, B.name AS bankName
				FROM transaction T
				LEFT JOIN category C1 ON C1.id = T.category_id
				LEFT JOIN bank_account A ON A.id = T.bank_account_id
				LEFT JOIN bank B ON B.id = A.bank_id
				WHERE T.is_deleted = 0
						AND T.category_id = " . $category_id . " AND T.category_id IS NOT NULL
						AND T.`transaction_date` >=  '" . $sd . "'
						AND T.`transaction_date` <=  '" . $ed . "')
			UNION
				(SELECT T.id, T.transaction_date, TS.type, T.description, T.is_uploaded, T.reconciled_date, TS.notes, TS.amount, A.name AS accountName, B.name AS bankName
				FROM transaction T
				LEFT JOIN bank_account A ON A.id = T.bank_account_id
				LEFT JOIN bank B ON B.id = A.bank_id
				LEFT JOIN transaction_split TS ON T.id = TS.transaction_id AND TS.is_deleted = 0
				LEFT JOIN category C2 ON C2.id = TS.category_id
				WHERE T.is_deleted = 0
						AND TS.category_id = " . $category_id . " AND T.category_id IS NULL
						AND T.`transaction_date` >=  '" . $sd . "'
						AND T.`transaction_date` <=  '" . $ed . "')
			ORDER BY transaction_date DESC, id DESC";
		$transactions->queryAll($sql);
		if ($transactions->numRows()) {
			foreach ($transactions as $transaction) {
				$transaction->amount = ($transaction->type == 'CHECK' || $transaction->type == 'DEBIT') ? -$transaction->amount: $transaction->amount;
			}
			$this->ajax->setData('result', $transactions);
		} else {
			$this->ajax->addError(new AjaxError("Error - No transactions found"));
		}
		$this->ajax->output();
	}

	private function _getEndDay() {
		$xx =  time();
		$yy = intval(strtotime($this->budget_start_date));
		$xx = ($xx - $yy) / (24 * 60 * 60);
		$xx = intval($xx / $this->budget_interval);
		return ($xx * $this->budget_interval);
	}

	public function _loadForecast($categories, $sd, $ed, $all = true) {
		$forecast = new forecast();
		$forecast->whereNotDeleted();
		$forecast->groupStart();
		$forecast->orWhere('last_due_date IS NULL ', NULL);
		$forecast->orWhere('last_due_date <= ', $ed);
		$forecast->groupEnd();
//		$forecast->where('first_due_date >= ', $sd);
		$forecast->where('first_due_date <= ', $ed);
		$forecast->result();
		if ($forecast->numRows()) {
			// set the next due date(s) for the forecasted expenses
			foreach ($forecast as $fc) {
				$next_due_dates = array();

				switch ($fc->every_unit) {
					case 'Days':
					case 'Weeks':
					case 'Months':
					case 'Years':
						$dd = array(strtotime($fc->first_due_date));
						$fdd = array($fc->first_due_date);
						break;
					case 'semi-monthly':
$first = 15;						// should come from DB record - in forecast entry make this a dropdown with 1 though 15
$second = 'last day of month';		// should come from DB record - in forecast entry make this a dropdown with 16 though (28-31) based on $first
						$fdd = array(date('Y-m') . '-' . sprintf("%02d", $first), date("Y-m-t"));
						$dd = array(strtotime($fdd[0]), strtotime($fdd[1]));
						break;
				}
				$x = 0;
				while ($this->_dateDiff($dd[$x], strtotime($ed)) < 0 &&												// while due_date < end_date
						(!$fc->last_due_date || $this->_dateDiff($dd[$x], strtotime($fc->last_due_date)) <= 0)) {	// ...AND (last_due_date is not set OR due_date <= last_due_date)
					if ($this->_dateDiff($dd[$x], strtotime($fc->first_due_date)) >= 0) {		// if due_date >= first_due_date
						if ($all || $dd[$x] > time()) {											// and due_date is gt now
							$next_due_dates[] = date('Y-m-d', $dd[$x]);							// ... then save this due date
						}
					}
					if (empty($dd[++$x])) {
						for ($y = 0; $y < count($fdd); $y++) {
							$dd[$y] = strtotime($fdd[$y] . " +" . $fc->every . " " . $fc->every_unit);	// set next due date
							$fdd[$y] = date('Y-m-d', $dd[$y]);
						}
						$x = 0;
					}
				}
				$fc->next_due_dates = $next_due_dates;
			}
		}
		return $forecast;
	}

	// compare two unix timestamps
	private function _dateDiff($d1, $d2) {
		return $d1 - $d2;
	}

	private function _getForecastByCategory($categories, $forecast, $start_date) {
		$sd = strtotime($start_date);																		// start date of forecast interval
		$ed = strtotime($start_date . " +" . $this->budget_interval . " " . $this->budget_interval_unit);	// end date of forecast interval
		$data = array('totals' => array(), 'adjustments' => array());
		// for each category
		foreach ($categories as $x => $category) {
			$data['totals'][$category->id] = NULL;
			// now for each forecast
			foreach ($forecast as $fc) {
				// if this forecast is for this category
				if ($fc->category_id == $category->id) {
					// if this forecast has due dates
					if (!empty($fc->next_due_dates)) {
						// check to see if any of the forecasted due date fall in the interval dates
						foreach ($fc->next_due_dates as $next_due_date) {
							$fd = strtotime($next_due_date);
							if ($fd >= $sd && $fd < $ed) {					// while next due date still inside forecast interval
								// found a forecated due date that falls in this interval
								switch ($fc->type) {
									case 'DSLIP':
									case 'CREDIT':
										$data['totals'][$category->id] += $fc->amount;
										// update the bank totals here and return as part of $data
//										if (empty($data['adjustments'][$category->id][$fc->bank_account_id])) {
//											$data['adjustments'][$category->id][$fc->bank_account_id] = $fc->amount;
//										} else {
//											$data['adjustments'][$category->id][$fc->bank_account_id] += $fc->amount;
//										}
										if (empty($data['interval_total'])) {
											$data['interval_total'] = $fc->amount;
										} else {
											$data['interval_total'] += $fc->amount;
										}
										break;
									case 'DEBIT':
									case 'CHECK':
										$data['totals'][$category->id] -= $fc->amount;
										// update the bank totals here and return as part of $data
//										if (empty($data['adjustments'][$category->id][$fc->bank_account_id])) {
//											$data['adjustments'][$category->id][$fc->bank_account_id] = -$fc->amount;
//										} else {
//											$data['adjustments'][$category->id][$fc->bank_account_id] -= $fc->amount;
//										}
										if (empty($data['interval_total'])) {
											$data['interval_total'] = $fc->amount;
										} else {
											$data['interval_total'] -= $fc->amount;
										}
										break;
								}
							}
						}
					}
				}
			}
		}
		return $data;
	}

}

// EOF