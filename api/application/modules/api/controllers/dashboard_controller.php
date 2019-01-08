<?php
/*
 * REST Dashboard controller
 */

require_once ('rest_controller.php');

class dashboard_controller Extends rest_controller {

	protected $debug = TRUE;

	public function __construct() {
		parent::__construct();
		
	}

	public function index() {
		$this->ajax->addError(new AjaxError("403 - Forbidden (dashboard/index)"));
		$this->ajax->output();
	}

	public function ytdTotals() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (dashboard/ytdTotals)"));
			$this->ajax->output();
		}

		$year = $this->input->get('year');
		if (!is_numeric($year) || $year < 2015) {
			$this->ajax->addError(new AjaxError("Invalid year"));
			$this->ajax->output();
		}
$this->ajax->setData('year', $year);
$this->ajax->setData('yearNOW', date('Y'));
		$categories = new category();
		$categories->whereNotDeleted();
		$categories->whereNotIn('id', Array(17,22));	// Do not load 'Transfer' and 'Opening Balance' categories
		$categories->orderBy('order');
		$categories->result();

		$select = array();
		foreach ($categories as $category) {
			$select[] = "SUM(CASE WHEN T.category_id = " . $category->id . " AND (T.type = 'CREDIT' OR T.type = 'DSLIP' OR T.type = 'RETURN' OR T.type = 'PAYMENT') THEN T.amount ELSE 0 END)" .
						" - SUM(CASE WHEN T.category_id = " . $category->id . " AND (T.type = 'CHECK' OR T.type = 'DEBIT' OR T.type = 'SALE') THEN T.amount ELSE 0 END)" .
						" + SUM(CASE WHEN TS.category_id = " . $category->id . " AND (TS.type = 'CREDIT' OR TS.type = 'DSLIP' OR TS.type = 'RETURN' OR TS.type = 'PAYMENT') THEN TS.amount ELSE 0 END)" .
						" - SUM(CASE WHEN TS.category_id = " . $category->id . " AND (TS.type = 'CHECK' OR TS.type = 'DEBIT' OR TS.type = 'SALE') THEN TS.amount ELSE 0 END) " .
						"AS total_" . $category->id;
		}

		$sql = array();
		$sql[] = "SELECT";
		$sql[] = implode(',', $select);
		$sql[] = "FROM transaction T";
		$sql[] = "LEFT JOIN transaction_split TS ON TS.transaction_id = T.id AND TS.is_deleted = 0";
		$sql[] = "WHERE YEAR(T.transaction_date) = '" . $year . "' AND T.is_deleted = 0";
		if ($year <= date('Y')) {
			$sql[] = " AND T.transaction_date <= now()";
		}

		$transactions = new transaction();
		$transactions->query(implode(' ', $sql));
		$this->ajax->setData('result', $transactions);

		$totals = array();
		// get any repeats for this interval
		if ($year < date('Y')) {
			$repeats = $this->loadRepeats($year . '-01-01', ($year+1) . '-01-01', 2);
			$repeats = $this->sumRepeats($repeats, $year . '-01-01', ($year+1) . '-01-01');
		} else {
			$repeats = $this->loadRepeats(date('Y-01-01'), date("Y-m-d", strtotime("+ 1 day")), 2);
			$repeats = $this->sumRepeats($repeats, date('Y-01-01'), date("Y-m-d", strtotime("+ 1 day")));
		}
		if (!empty($repeats)) {
			foreach ($repeats as $rp) {
				if (!empty($rp['totals'])) {
					foreach($rp['totals'] as $category_id => $category_total) {
						if (!empty($totals[$category_id])) {
							$totals[$category_id] += $category_total;
						} else {
							$totals[$category_id] = $category_total;
						}
					}
				}
			}
		}

		// get the past forecasts for this interval
		$forecasted = $this->loadForecast($year . '-01-01', ($year+1) . '-01-01', (($year <= date('Y')) ? 2: 0));
		$forecast = $this->forecastIntervals($categories, $forecasted, $year . '-01-01', ($year+1) . '-01-01');
		if (!empty($forecast)) {
			foreach ($forecast as $fc) {
				if (!empty($fc['totals'])) {
					foreach($fc['totals'] as $category_id => $category_total) {
						if (!empty($totals[$category_id])) {
							$totals[$category_id] += $category_total;
						} else {
							$totals[$category_id] = $category_total;
						}
					}
				}
			}
		}
		$this->ajax->setData('forecast', $totals);

		$this->ajax->setData('year', $year);
		$this->ajax->output();
	}

	public function forecast() {
		
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("Error: 403 Forbidden - (dashboard/forecast)"));
			$this->ajax->output();
		}

		$year = $this->input->get('year');
		if (!$year || !is_numeric($year)) {
			$this->ajax->addError(new AjaxError("Error: Invalid year - (dashboard/forecast)"));
			$this->ajax->output();
		}

		$category_id = $this->input->get('category_id');
		if ($category_id == 0 || !is_numeric($category_id)) {
			$this->ajax->addError(new AjaxError("Error: Invalid category id - (dashboard/forecast)"));
			$this->ajax->output();
		}

		$forecast = array();
		// get any repeats for this interval
		if ($year < date('Y')) {
			$repeats = $this->loadRepeats($year . '-01-01', ($year+1) . '-01-01', 2, $category_id);
		} else {
			$repeats = $this->loadRepeats(date('Y-01-01'), date("Y-m-d", strtotime("+ 1 day")), 2, $category_id);
		}
		foreach ($repeats as $repeat) {
			foreach ($repeat->next_due_dates as $next_due_date) {
				$ndd = strtotime($next_due_date);
				while (!empty($forecast[$ndd])) {
					$ndd++;
				}
				$forecast[$ndd]['date'] = $next_due_date;
				$forecast[$ndd]['description'] = $repeat->description;
				$forecast[$ndd]['notes'] = $repeat->notes;
				$forecast[$ndd]['type'] = $repeat->type;
				$forecast[$ndd]['amount'] = $repeat->amount;
				$forecast[$ndd]['vendor'] = $repeat->vendor->name;
				$forecast[$ndd]['account'] = $repeat->bank_account->bank->name . ' ' . $repeat->bank_account->name;
				$forecast[$ndd]['ftype'] = 1;
			}
		}
		// get the past forecasts for this interval
		$forecasted = $this->loadForecast($year . '-01-01', ($year+1) . '-01-01', (($year <= date('Y')) ? 2: 0), $category_id);
		if (!empty($forecasted)) {
			foreach ($forecasted as $fc) {
				foreach ($fc->next_due_dates as $next_due_date) {
					$ndd = strtotime($next_due_date);
					while (!empty($forecast[$ndd])) {
						$ndd++;
					}
					$forecast[$ndd]['date'] = $next_due_date;
					$forecast[$ndd]['description'] = $fc->description;
					$forecast[$ndd]['notes'] = $fc->notes;
					$forecast[$ndd]['type'] = $fc->type;
					$forecast[$ndd]['amount'] = $fc->amount;
					$forecast[$ndd]['account'] = $fc->bank_account->bank->name . ' ' . $fc->bank_account->name;
					$forecast[$ndd]['ftype'] = 2;
				}
			}
		}
		ksort($forecast);
		$totals = array();
		foreach ($forecast as $fc) {
			$totals[] = $fc;
		}
		$this->ajax->setData('result', array_reverse($totals));
		$this->ajax->output();
	}

	public function these() {

		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("Error: 403 Forbidden - (dashboard/these)"));
			$this->ajax->output();
		}

		$year = $this->input->get('year');
		if (!$year || !is_numeric($year)) {
			$this->ajax->addError(new AjaxError("Error: Invalid year - (dashboard/these)"));
			$this->ajax->output();
		}

		$category_id = $this->input->get('category_id');
		if ($category_id == 0 || !is_numeric($category_id)) {
			$this->ajax->addError(new AjaxError("Error: Invalid category id - (dashboard/these)"));
			$this->ajax->output();
		}

		$transactions = new transaction();
		$sql = "(SELECT T.transaction_date, T.type, T.description, T.notes, T.amount, V1.name AS vendorName
				FROM transaction T
				LEFT JOIN vendor V1 on V1.id = T.vendor_id
				WHERE T.is_deleted = 0
					AND T.category_id = " . $category_id . " AND T.category_id IS NOT NULL
					AND YEAR(T.`transaction_date`) = " . $year . ")
			UNION
				(SELECT T.transaction_date, T.type, T.description, TS.notes, TS.amount, V2.name AS vendorName
				FROM transaction T
				LEFT JOIN transaction_split TS ON T.id = TS.transaction_id
				LEFT JOIN vendor V2 on V2.id = TS.vendor_id
				WHERE T.is_deleted = 0
					AND TS.category_id = " . $category_id . " AND T.category_id IS NULL
					AND YEAR(T.`transaction_date`) = " . $year . ")
			ORDER BY transaction_date DESC";
		$transactions->queryAll($sql);
		if ($transactions->numRows()) {
			foreach ($transactions as $transaction) {
				$transaction->amount = ($transaction->type == 'CHECK' || $transaction->type == 'DEBIT' || $transaction->type == 'SALE') ? -$transaction->amount: $transaction->amount;
			}
			$this->ajax->setData('result', $transactions);
		} else {
			$this->ajax->addError(new AjaxError("Error: No transactions found - (dashboard/these)"));
		}
		$this->ajax->output();
	}

	public function getBankBalances() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("Error: 403 Forbidden - (dashboard/getBankBalances)"));
			$this->ajax->output();
		}

		$bank_accounts = new bank_account();
		$bank_accounts->whereNotDeleted();
		$bank_accounts->result();

		$balances = array();
		if ($bank_accounts) {
			foreach ($bank_accounts as $account) {
				$transactions = new transaction();
				$sql = "SELECT		a.id, a.bank_account_id, a.transaction_date, a.bank_account_balance,
									CONCAT(bank.name, ' ', bank_account.name) AS account_name, bank_account.date_opened, bank_account.date_closed
						FROM		transaction a
						JOIN		bank_account ON bank_account.id = a.bank_account_id
						JOIN		bank ON bank.id = bank_account.bank_id
						WHERE		a.is_uploaded = 1 AND a.is_deleted = 0 AND a.bank_account_id = $account->id
						ORDER BY	a.transaction_date DESC, a.id DESC
						LIMIT		1";
				$transactions->queryAll($sql);
				$balances[] = $transactions[0];
			}
		}
		$this->ajax->setData('result', $balances);
//		$transactions = new transaction();
//		$sql = "SELECT		a.id, a.bank_account_id, a.transaction_date, a.bank_account_balance,
//							CONCAT(bank.name, ' ', bank_account.name) AS account_name, bank_account.date_opened, bank_account.date_closed
//				FROM		transaction a
//				JOIN		bank_account ON bank_account.id = a.bank_account_id
//				JOIN		bank ON bank.id = bank_account.bank_id
//				WHERE		(a.is_uploaded = 1 || a.category_id = 22) AND a.is_deleted = 0
//							AND a.transaction_date = (
//								SELECT MAX( b.transaction_date )
//								FROM transaction b
//								WHERE (b.is_uploaded = 1 || b.category_id = 22) AND b.is_deleted = 0
//								AND a.bank_account_id = b.bank_account_id
//							 )
//							AND a.id = (
//								SELECT MAX( c.id )
//								FROM transaction c
//								WHERE (c.is_uploaded = 1 || c.category_id = 22) AND c.is_deleted = 0
//								AND a.bank_account_id = c.bank_account_id
//								AND a.transaction_date = c.transaction_date
//							 )
//				GROUP BY	a.bank_account_id";
//		$transactions->queryAll($sql);
//		$this->ajax->setData('result', $transactions);
		$this->ajax->output();
	}
}

// EOF
