<?php
/*
 * REST Budget controller
 */

require_once ('rest_controller.php');

class budget_controller Extends rest_controller {

	public function __construct() {
		parent::__construct();
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
				$this->ajax->addError(new AjaxError("Invalid budget_mode setting (budget/load)"));
				$this->ajax->output();
		}

		$transactions = new transaction();
		$transactions->queryAll(implode(' ', $sql));

		// get the accounts
		$accounts = new bank_account();
		$accounts->select("bank_account.id, CONCAT(`bank`.`name`,' ',`bank_account`.`name`) as name", FALSE);
		$accounts->join('bank', 'bank.id = bank_account.bank_id');
		$accounts->where('bank_account.is_deleted', 0);
		$accounts->result();

		// get any repeats for this interval
		$repeats = $this->loadRepeats($categories, $sd, $ed);
		$repeats = $this->sumRepeats($repeats, $sd, $ed);

		// get the future forecast
		$forecasted = $this->loadForecast($categories, $sd, $ed);
		$forecast = $this->forecastIntervals($categories, $forecasted, $sd, $ed);

		foreach ($forecast as $x => &$fc) {
			foreach ($fc['totals'] as $category_id => $category_total) {
				if (!empty($repeats[$x]['totals'][$category_id])) {
					$fc['totals'][$category_id] = $category_total + $repeats[$x]['totals'][$category_id];
					$fc['types'][$category_id] = 11;
				} else {
					$fc['types'][$category_id] = 1;
				}
			}
		}

		$data = array();
		$data['interval_total'] = 0;

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
					$output[] = $data;

					$data = array();
					$data['interval_total'] = 0;

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
						}
					}
				}
			}
		}

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
				$data['interval_beginning']	= date('c', strtotime($isd));
				$data['interval_ending']	= date('c', strtotime($ied . " 23:59:59"));
				$data['interval_total']		= 0;
				$output[] = $data;
			}
		}

		$_output = array();
		$y = 0;
		foreach ($output as $x => $intervalx) {
			// now calculate the difference between actual and forecast
			foreach ($forecast[$x]['totals'] as $category_id => $category_total) {
				if (isset($category_total) || isset($output[$x]['totals'][$category_id])) {
					$output[$x]['category_difference'][$category_id] = $output[$x]['totals'][$category_id] - $category_total;
				}
			}
			$output[$x]['difference'] = $forecast[$x]['interval_total'] - $output[$x]['interval_total'];
			$_output[$y++] = $forecast[$x];
			$_output[$y++] = $output[$x];
		}

		$this->ajax->setData('result', $_output);

		$this->ajax->output();
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

		$interval_ending = $this->input->get('interval_ending');
		if (!$interval_ending || !strtotime($interval_ending)) {
			$this->ajax->addError(new AjaxError("Invalid interval ending - budget/these"));
			$this->ajax->output();
		}
		$interval_ending = explode('T', $interval_ending);
		$ed = date('Y-m-d', strtotime($interval_ending[0] . ' +1 Day'));

		$category_id = $this->input->get('category_id');
		if ($category_id == 0 || !is_numeric($category_id)) {
			$this->ajax->addError(new AjaxError("Invalid category id - budget/these"));
			$this->ajax->output();
		}

		$repeats = FALSE;
		$forecasts = FALSE;
		$forecasted = array();
		$all = $this->input->get('all');
		$forecast = $this->input->get('forecast');
		switch ($forecast) {
			case 1:
			case 2:
				// get any forecasted transactions
				$forecasts = $this->loadForecast(array('id' => $category_id), $sd, $ed, $all);
				break;
			case 11:
			case 12:
				// get any forecasted transactions
				$forecasts = $this->loadForecast(array('id' => $category_id), $sd, $ed, $all);
			case 10:
				// get any repeated transactions
				$repeats = $this->loadRepeats(array('id' => $category_id), $sd, $ed, $all);
				break;
			case 0:
				break;
		}
		if ($repeats) {
			// now format the forecast
			foreach ($repeats as $rp) {
				foreach($rp->next_due_dates as $next_due_date) {
					$data = array();
					$data['id']					= $rp->id;
					$data['transaction_date']	= $next_due_date;
					$data['type']				= (!empty($rp->split_type)) ? $rp->split_type: $rp->type;
					$data['description']		= (!empty($rp->split_description)) ? $rp->split_description: $rp->description;
					$data['is_repeat']			= 1;
					$data['is_uploaded']		= 0;
					$data['reconciled_date']	= NULL;
					$data['notes']				= $rp->notes;
					if (empty($rp->split_amount)) {
						$data['amount']			= ($rp->type == 'CREDIT' || $rp->type == 'DSLIP') ? $rp->amount: -$rp->amount;
					} else {
						$data['amount']			= ($rp->spli_type == 'CREDIT' || $rp->split_type == 'DSLIP') ? $rp->split_amount: -$rp->split_amount;
					}
					$data['accountName']		= $rp->bank_account->name;
					$data['bankName']			= $rp->bank_account->bank->name;
					$forecasted[] = $data;
				}
			}
		}
		if ($forecasts) {
			// now format the forecast
			foreach ($forecasts as $fc) {
				foreach($fc->next_due_dates as $next_due_date) {
					$data = array();
					$data['id']					= $fc->id;
					$data['transaction_date']	= $next_due_date;
					$data['type']				= $fc->type;
					$data['description']		= $fc->description;
					$data['is_forecast']		= 1;
					$data['is_uploaded']		= 0;
					$data['reconciled_date']	= NULL;
					$data['notes']				= $fc->notes;
					$data['amount']				= ($fc->type == 'CREDIT' || $fc->type == 'DSLIP') ? $fc->amount: -$fc->amount;
					$data['accountName']		= $fc->bank_account->name;
					$data['bankName']			= $fc->bank_account->bank->name;
					$forecasted[] = $data;
				}
			}
		}

		if ($forecast == 1 || $forecast == 11) {
			$this->ajax->setData('result', $forecasted);
		} else {
			// get actual transactions
			$transactions = new transaction();
			$sql = "(SELECT T.id, T.transaction_date, T.type, T.description, T.is_uploaded, T.reconciled_date, T.notes, T.amount, A.name AS accountName, B.name AS bankName
					FROM transaction T
					LEFT JOIN category C1 ON C1.id = T.category_id
					LEFT JOIN bank_account A ON A.id = T.bank_account_id
					LEFT JOIN bank B ON B.id = A.bank_id
					WHERE T.is_deleted = 0
							AND T.category_id = " . $category_id . " AND T.category_id IS NOT NULL
							AND T.`transaction_date` >=  '" . $sd . "'
							AND T.`transaction_date` <  '" . $ed . "')
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
							AND T.`transaction_date` <  '" . $ed . "')
				ORDER BY transaction_date ASC, id ASC";
			$transactions->queryAll($sql);
			$output = array();
			$f = 0;
			// merge forecasted and actual transactions in date order
			foreach ($transactions as $transaction) {
				$transaction->amount = ($transaction->type == 'CHECK' || $transaction->type == 'DEBIT') ? -$transaction->amount: $transaction->amount;
				if (empty($forecasted[$f]) || strtotime($transaction->transaction_date) <= strtotime($forecasted[$f]['transaction_date'])) {
					$output[] = $transaction->toArray();
				} else {
					$output[] = $forecasted[$f];
					$f++;
				}
			}
			for(; $f < count($forecasted); $f++) {
				$output[] = $forecasted[$f];
			}
			$this->ajax->setData('result', $output);
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

}

// EOF