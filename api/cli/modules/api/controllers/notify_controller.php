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

		log_message("debug", "CRON notify running");
		$accounts = new account();
		$accounts->whereNotDeleted();
		$accounts->where('is_active', 1);
		$accounts->result();
		if ($accounts->numFields()) {
			foreach ($accounts as $account) {
				log_message("debug", 'CRON notify account budgettr_' . $account->db_suffix_name);
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
						$repeat->smsSent = date('Y-m-d H:i:s');
						$repeat->save();
						isset($repeat->vendor);
						$to = $account->phone . "@vtext.com";
						if ($repeat->type == 'DEBIT' || $repeat->type == 'CHECK' || $repeat->type == 'SALE' || $repeat->type == 'PAYMENT') {
							$subject = "Payment to " . $repeat->vendor->name;
							$type = ' for ';
						} else {
							$subject = 'Credit from ' . $repeat->vendor->name;
							$type = ' from ';
						}
						$msg = "$" . $repeat->amount . $type . $repeat->description . " is due on " . date('l F j, Y', strtotime($repeat->next_due_date));
						log_message("debug", 'CRON notify ' . $to . ' ' . $subject . ' ' . $msg);
						mail($to, $subject, $msg, "From: BudgetTrackerPro\r\n");
					}
				}
			}
		}
		log_message("debug", "CRON notify finished");
	}

}

// EOF