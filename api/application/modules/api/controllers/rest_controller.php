<?php
/*
 * REST controller
 */

class rest_controller Extends EP_Controller {

	protected $debug = TRUE;

	protected $budget_interval = FALSE;
	protected $budget_interval_unit = FALSE;

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
			case 'semi-monthly':
				$this->budget_interval = 1;
				$this->budget_interval_unit = 'Months';
				break;
			case 'monthly':
				$this->budget_interval = 1;
				$this->budget_interval_unit = 'Months';
				break;
			default:
				$this->ajax->addError(new AjaxError("Invalid budget_mode setting (rest controller)"));
				$this->ajax->output();
		}

		$class = get_class($this);
		if ($class !== 'upload_controller' && $class !== 'livesearch_controller') {
			if ($resetBalances = $this->appdata->get('resetBalances')) {	// get resets
				foreach($resetBalances as $account_id => $date) {
					// for each reset adjust the account balance
					$this->_adjustAccountBalances($date, $account_id);
				}
				$this->appdata->remove('resetBalances');	// remove the reset balances from app data
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
	 * @param type $sd
	 * @param type $ed
	 * @param type $all 0 = all
	 * @return transactions with next due dates
	 * @throws Exception
	 */
	protected function loadRepeats($sd, $ed, $all = 0, $category_id = FALSE) {
		$transactions = new transaction_repeat();
		if ($all == 1) {
			$transactions->groupStart();
			$transactions->orWhere('last_due_date IS NULL ', NULL);
			$transactions->orGroupStart();
			$transactions->where('last_due_date >= ', $sd);
			$transactions->where('last_due_date >= now()', NULL, FALSE);
			$transactions->groupEnd();
			$transactions->groupEnd();
			$transactions->where('next_due_date < ', $ed);
		} else {
			$transactions->groupStart();
			$transactions->orWhere('last_due_date IS NULL ', NULL);
			$transactions->orWhere('last_due_date >= ', $sd);
			$transactions->groupEnd();
		}
		if ($category_id) {
			$transactions->where('category_id', $category_id);
		}
		$transactions->groupStart();
		$transactions->orWhere('last_due_date IS NULL', NULL, FALSE);
		$transactions->orWhere('last_due_date >= next_due_date', NULL, FALSE);
		$transactions->groupEnd();
		$transactions->where('first_due_date < ', $ed);
		$transactions->where('is_deleted', 0);
		$transactions->orderBy('next_due_date', 'ASC');
		$transactions->result();
		$now = strtotime(date('m/d/Y'));
		// now calculate all repeat due dates for given period
		foreach ($transactions as $transaction) {
			if ($category_id) {
				isset($transaction->vendor);
			}
			$next_due_dates = array();
			$next_due_date = $transaction->first_due_date;
			$every = 0;
			while (strtotime($next_due_date) < strtotime($ed)) {
				switch ($transaction->every_unit) {
					case 'Day':
						$next_due_date = date('Y-m-d', strtotime($next_due_date . ' +' . $every . ' day'));
						break;
					case 'Week':
						$date = strtotime($next_due_date . ' +' . $every . ' week');
						$dayofweek = date('w', $date);
						$every_day = date('N', $date);
						$next_due_date = date('Y-m-d', strtotime(date('Y-m-d', $date) . ' +' . ($every_day - $dayofweek).' day'));
						break;
					case 'Month':
						if ($transaction->everyDay === NULL && $transaction->day === NULL) {
							$next_due_date = date('Y-m-' . date('d', strtotime($next_due_date)), strtotime($next_due_date . ' +' . $every . ' month'));
						} else {
							$next_due_date = date('Y-m-d', strtotime($transaction->everyDay . ' ' . $transaction->day . ' of ' . date('Y-m', strtotime($next_due_date . ' +' . $every . ' month'))));
						}
						break;
					case 'Year':
						$every_date = date('d', strtotime($transaction->first_due_date));
						$every_month = date('m', strtotime($transaction->first_due_date));
						$next_due_date = date('Y-' . $every_month . '-' . $every_date, strtotime($next_due_date . ' +' . $every . ' year'));
						break;
					case 'Quarter':
					default:
						throw new Exception('Invalid transaction_repeat->every_unit');
						break;
				}
				$ndd = strtotime($next_due_date);
				if ($ndd >= strtotime($sd) && $ndd < strtotime($ed) && (!$transaction->last_due_date || $ndd <= strtotime($transaction->last_due_date))) {
					if (($all == 0)															// ...we want all repeats
							||																//			or
						($all == 1 && $ndd >= strtotime($transaction->next_due_date))		// ... we want future repeats
							||																//			or
						($all == 2 && $ndd <= $now)) {										// ... we want past repeats
						$next_due_dates[] = $next_due_date;									// ... then save this due date
					}
				}
				$every = $transaction->every;
			}
			$transaction->next_due_dates = $next_due_dates;
		}
		return $transactions;
	}
	
	protected function sumRepeats($transactions, $sd, $ed) {
		// now sum the repeats for each of the requested intervals
		$offset = 0;
		$repeats = array();
		while (strtotime($sd . ' +' . $offset . ' ' . $this->budget_interval_unit) < strtotime($ed)) {
			$interval_beginning = strtotime($sd . ' +' . $offset . ' ' . $this->budget_interval_unit);
			$interval_ending = date('Y-m-d', strtotime($sd . ' +' . ($offset + $this->budget_interval) . ' ' . $this->budget_interval_unit));
			$interval_ending = strtotime($interval_ending . ' -1 Day');

			$interval = array();
			foreach ($transactions as $transaction) {
				foreach($transaction->next_due_dates as $next_due_date) {
					if (strtotime($next_due_date) >= $interval_beginning && strtotime($next_due_date) <= $interval_ending) {
						$bb = $transaction->bank_account_id;
						$amount = NULL;
						switch ($transaction->type) {
							case 'DSLIP':
							case 'CREDIT':
							case 'RETURN':
							case 'PAYMENT':
								$amount = $transaction->amount;
								break;
							case 'DEBIT':
							case 'CHECK':
							case 'SALE':
								$amount = -$transaction->amount;
								break;
						}
						$cc = $transaction->category_id;
						if (empty($interval['totals'][$cc])) {
							$interval['totals'][$cc]		= $amount;				// set the category totals
						} else {
							$interval['totals'][$cc]		+= $amount;				// add the category totals
						}
						if (empty($interval['adjustments'][$bb])) {
							$interval['adjustments'][$bb]	= $amount;				// set the bank account balance adjustments
						} else {
							$interval['adjustments'][$bb]	+= $amount;				// add the category totals
						}
						if (empty($interval['interval_total'])) {
							$interval['interval_total']		= $amount;				// set the interval total
						} else {
							$interval['interval_total']		+= $amount;				// add the category totals
						}
					}
				}
			}
			$interval['interval_beginning']	= date('c', $interval_beginning);
			$interval['interval_ending']	= date('c', strtotime(date('Y-m-d', $interval_ending) . ' 23:59:59'));
			$repeats[] = $interval;

			$offset += $this->budget_interval;
		}
		return $repeats;
	}

	/**
	 * 
	 * @param type $categories
	 * @param type $sd
	 * @param type $ed
	 * @param type $all 0 = get all, 1 = future, 2 = past
	 * @return forecast
	 */
	protected function loadForecast($sd, $ed, $all = 0, $category_id = FALSE) {
		$forecast = new forecast();
		$forecast->whereNotDeleted();
		$forecast->groupStart();
		$forecast->orWhere('last_due_date IS NULL ', NULL);
//		$forecast->orWhere('last_due_date < ', $ed);
		$forecast->orWhere('last_due_date >= ', $sd);
		$forecast->groupEnd();
		$forecast->where('first_due_date < ', $ed);
		if ($category_id) {
			$forecast->where('category_id', $category_id);
		}
		$forecast->result();
		if ($forecast->numRows()) {
			$now = strtotime(date('m/d/Y'));
			// set the next due date(s) for the forecasted expenses
			foreach ($forecast as $fc) {
				if ($category_id) {
					isset($fc->vendor);
				}
				isset($fc->bank_account->bank);
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
					if ($this->_dateDiff($dd[$x], strtotime($fc->first_due_date)) >= 0			// if due_date >= first_due_date
							&&
						$this->_dateDiff($dd[$x], strtotime($sd)) >= 0) {						// and due_date >= start date and ...
						if (($all == 0)															// ...we want all forecasts
								||																//			or
							($all == 1 && $dd[$x] >= $now)										// ... we want future forecasts
								||																//			or
							($all == 2 && $dd[$x] <= $now)) {									// ... we want past forecasts
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

	protected function forecastIntervals($categories, $forecasted, $sd, $ed) {
		// now sum the expenses for the forecast intervals
		$offset = 0;
		$forecast = array();
		$xx = 0;
		while (strtotime($sd . ' +' . $offset . ' ' . $this->budget_interval_unit) < strtotime($ed)) {
			$interval_beginning = date('Y-m-d', strtotime($sd . ' +' . $offset . ' ' . $this->budget_interval_unit));
			$interval_ending = date('Y-m-d', strtotime($sd . ' +' . ($offset + $this->budget_interval) . ' ' . $this->budget_interval_unit));
			$interval_ending = date('Y-m-d', strtotime($interval_ending . ' -1 Day'));

			$data = $this->getForecastByCategory($categories, $forecasted, $interval_beginning);

			$forecast[$xx]['totals']				= $data['totals'];			// load the category totals
			$forecast[$xx]['adjustments']			= $data['adjustments'];		// load the bank account balance adjustments
			$forecast[$xx]['interval_total']		= (!empty($data['interval_total'])) ? $data['interval_total']: 0;	// load the interval total
			$forecast[$xx]['interval_beginning']	= date('c', strtotime($interval_beginning));
			$forecast[$xx]['interval_ending']		= date('c', strtotime($interval_ending . ' 23:59:59'));
			$forecast[$xx]['forecast']				= 1;						// mark this interval as a forecast
			$xx++;
			$offset += $this->budget_interval;
		}
		return $forecast;
	}

	protected function getForecastByCategory($categories, $forecast, $start_date) {
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
									case 'RETURN':
									case 'PAYMENT':
										$data['totals'][$category->id] += $fc->amount;
										// update the bank totals here and return as part of $data
										if (empty($data['adjustments'][$category->id][$fc->bank_account_id])) {
											$data['adjustments'][$category->id][$fc->bank_account_id] = $fc->amount;
										} else {
											$data['adjustments'][$category->id][$fc->bank_account_id] += $fc->amount;
										}
										if (empty($data['interval_total'])) {
											$data['interval_total'] = $fc->amount;
										} else {
											$data['interval_total'] += $fc->amount;
										}
										break;
									case 'DEBIT':
									case 'CHECK':
									case 'SALE':
										$data['totals'][$category->id] -= $fc->amount;
										// update the bank totals here and return as part of $data
										if (empty($data['adjustments'][$category->id][$fc->bank_account_id])) {
											$data['adjustments'][$category->id][$fc->bank_account_id] = -$fc->amount;
										} else {
											$data['adjustments'][$category->id][$fc->bank_account_id] -= $fc->amount;
										}
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

	/**
	 * 
	 * @name resetAccountBalances
	 * @type {function}
	 */
	public function reconcileTransactions() {
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
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
			$this->ajax->addError(new AjaxError("Invalid reconcile transaction date or account id (rest/reconcileTransactions)"));
		}
		$this->ajax->output();
	}

	/**
	 * Saves account id and date to reset balances if necessary, if the date is newer than the 
	 * already saved date then it ignores request
	 * @name resetBalances
	 * @param type $resets
	 */
	protected function resetBalances($resets) {
		$update = FALSE;
		$resetBalances = $this->appdata->get('resetBalances');	// get existing resets
		foreach ($resets as $account_id => $date) {
			if (empty($resetBalances[$account_id]) || strtotime($date) < strtotime($resetBalances[$account_id])) {
				// found a lower date to reset to
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
			// get the last transaction date from which to reset the bank account balance
			$transaction = new transaction();
			$transaction->select('MAX(transaction_date) AS date');
			$transaction->whereNotDeleted();
			$transaction->where("transaction_date < '" . $transaction_date . "'", NULL, FALSE);
			$transaction->where("bank_account_id", $account_id);
			$transaction->limit(1);
			$transaction->row();
			if (!empty($transaction->date)) {
				$transaction_date = $transaction->date;
			}
			// now get the transactions that need the balance to be reset
			$transactions = new transaction();
			$transactions->whereNotDeleted();
			$transactions->where("transaction_date >= '" . $transaction_date . "'", NULL, FALSE);
			$transactions->where("bank_account_id", $account_id);
			$transactions->orderBy('transaction_date', 'ASC');
			$transactions->orderBy('id', 'ASC');
			$transactions->result();
			if ($transactions->numRows()) {
				$first = TRUE;
				$bank_account_balances = array();
				foreach ($transactions as $transaction) {
					if (!$first) {// || empty($transaction->bank_account_balance)) {
						switch ($transaction->type) {
							case 'DEBIT':
							case 'CHECK':
							case 'SALE':
								$bank_account_balances[$transaction->bank_account_id] -= $transaction->amount;
								break;
							case 'CREDIT':
							case 'DSLIP':
							case 'RETURN':
							case 'PAYMENT':
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

	/*
	 * sd = we need the first available balance before this date
	 * bank_account_id = bank account id
	 */
	protected function getBankAccountBalance($sd, $account_id) {
		$transaction = new transaction();
		$transaction->select('transaction.*, bank_account.date_opened, bank_account.date_closed');
		$transaction->join('bank_account', 'transaction.bank_account_id = bank_account.id');
		$transaction->where('transaction.is_deleted', 0);
		$transaction->where("transaction.transaction_date < '" . $sd . "'", NULL, FALSE);
		$transaction->where('transaction.bank_account_id', $account_id);
		$transaction->orderBy('transaction.transaction_date', 'DESC');
		$transaction->limit(1);
		$transaction->row();
		if ($transaction->numRows()) {
			return array($transaction->transaction_date, $transaction->bank_account_balance, $transaction->reconciled_date, $transaction->date_opened, $transaction->date_closed);
		} else {
			return array(NULL, 0, NULL, NULL, NULL);
		}
	}
}

// EOF