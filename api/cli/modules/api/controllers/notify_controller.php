<?php
/*
 * Notify controller
 */

class notify_controller Extends EP_Controller {

	protected $debug = TRUE;

	protected $budget_interval = FALSE;
	protected $budget_interval_unit = FALSE;

	public function __construct() {

		parent::__construct();
	}

	public function index() {

		$accounts = new account();
		$accounts->whereNotDeleted();
		$accounts->where('is_active', 1);
		$accounts->result();
		if ($accounts->numFields()) {
			foreach ($accounts as $account) {
				$this->switchDatabase('budgettr_' . $account->db_suffix_name);
				$repeats = new transaction_repeat();
				$repeats->whereNotDeleted();
				$repeats->where('next_due_date <= now()', FALSE, FALSE);
				$repeats->groupStart();
				$repeats->orWhere('last_due_date IS NULL', FALSE, FALSE);
				$repeats->orWhere('last_due_date >= now()', FALSE, FALSE);
				$repeats->groupEnd();
				$repeats->result();
				if ($repeats->numRows()) {
					foreach ($repeats as $repeat) {
						isset($repeat->vendor);
						$to = $account->phone . "@vtext.com\n";
						if ($repeat->type == 'DEBIT' || $repeat->type == 'CHECK' || $repeat->type == 'SALE' || $repeat->type == 'PAYMENT') {
							$subject = "Payment to " . $repeat->vendor->name;
							$type = ' for ';
						} else {
							$subject = 'Credit from ' . $repeat->vendor->name;
							$type = ' from ';
						}
						$msg = "$" . $repeat->amount . $type . $repeat->description . " is due on " . date('l F j, Y', strtotime($repeat->next_due_date)) . "\n";
						mail($to, $subject, $msg, "From: BudgetTrackerPro\r\n");
					}
				}
			}
		}
	}

}

// EOF