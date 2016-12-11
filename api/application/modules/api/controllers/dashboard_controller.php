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
		$categories = new category();
		$categories->whereNotDeleted();
		$categories->whereNotIn('id', Array(17,22));	// Do not load Transfer and Opening Balance
		$categories->orderBy('order');
		$categories->result();

		$select = array();
		foreach ($categories as $category) {
			$select[] = "SUM(CASE WHEN T.category_id = " . $category->id . " AND T.type = 'CREDIT' THEN T.amount ELSE 0 END)" .
						" + SUM(CASE WHEN T.category_id = " . $category->id . " AND T.type = 'DSLIP' THEN T.amount ELSE 0 END)" .
						" - SUM(CASE WHEN T.category_id = " . $category->id . " AND T.type = 'CHECK' THEN T.amount ELSE 0 END)" .
						" - SUM(CASE WHEN T.category_id = " . $category->id . " AND T.type = 'DEBIT' THEN T.amount ELSE 0 END) " .
						" + SUM(CASE WHEN TS.category_id = " . $category->id . " AND TS.type = 'CREDIT' THEN TS.amount ELSE 0 END)" .
						" + SUM(CASE WHEN TS.category_id = " . $category->id . " AND TS.type = 'DSLIP' THEN TS.amount ELSE 0 END)" .
						" - SUM(CASE WHEN TS.category_id = " . $category->id . " AND TS.type = 'CHECK' THEN TS.amount ELSE 0 END)" .
						" - SUM(CASE WHEN TS.category_id = " . $category->id . " AND TS.type = 'DEBIT' THEN TS.amount ELSE 0 END) " .
						"AS total_" . $category->id;
		}

		$sql = array();
		$sql[] = "SELECT";
		$sql[] = implode(',', $select);
		$sql[] = "FROM transaction T";
		$sql[] = "LEFT JOIN transaction_split TS ON TS.transaction_id = T.id AND TS.is_deleted = 0";
		$sql[] = "WHERE YEAR(T.transaction_date) = '" . $year . "' AND T.transaction_date <= now() AND T.is_deleted = 0";

		$transactions = new transaction();
		$transactions->query(implode(' ', $sql));
		$this->ajax->setData('result', $transactions);

		$totals = array();
		// get any repeats for this interval
		$repeats = $this->loadRepeats($year . '-01-01', ($year+1) . '-01-01', 2);
		$repeats = $this->sumRepeats($repeats, $year . '-01-01', ($year+1) . '-01-01');
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
		$forecasted = $this->loadForecast($year . '-01-01', ($year+1) . '-01-01', 2);
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

		$this->ajax->setData('year', date('Y'));
		$this->ajax->output();
	}

	public function these() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("Error: 403 Forbidden - (dashboard/these)"));
			$this->ajax->output();
		}

		$year	= $this->input->get('year');
		if (!$year || !is_numeric($year)) {
			$this->ajax->addError(new AjaxError("Error: Invalid year - (dashboard/these)"));
			$this->ajax->output();
		}

		$category_id	= $this->input->get('category_id');
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
				$transaction->amount = ($transaction->type == 'CHECK' || $transaction->type == 'DEBIT') ? -$transaction->amount: $transaction->amount;
			}
			$this->ajax->setData('result', $transactions);
		} else {
			$this->ajax->addError(new AjaxError("Error: No transactions found - (dashboard/these)"));
		}
		$this->ajax->output();
	}

}

// EOF