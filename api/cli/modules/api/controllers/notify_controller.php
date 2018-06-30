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

		echo "Here I am 22222 \n";
		$repeats = new transaction_repeat();
		$repeats->limit(20);
		$repeats->result();
print $repeats;
	}

}

// EOF