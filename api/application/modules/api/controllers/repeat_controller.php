<?php
/*
 * REST Repeat controller
 */

require_once ('rest_controller.php');

class repeat_controller Extends rest_controller {

	protected $debug = TRUE;

	public function __construct() {
		parent::__construct();
	}

	public function index() {
		$this->ajax->addError(new AjaxError("403 - Forbidden (repeat/index)"));
		$this->ajax->output();
	}

	public function get() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (repeat/loadAll)"));
			$this->ajax->output();
		}

		$params = $this->input->get();

		$last_due_date		= $params['last_due_date'];
		$name				= (!empty($params['name'])) ? $params['name']: FALSE;
		$bank_account_id	= (!empty($params['bank_account_id'])) ? $params['bank_account_id']: FALSE;
		$category_id		= (!empty($params['category_id'])) ? $params['category_id']: FALSE;
		$amount				= (!empty($params['amount'])) ? $params['amount']: FALSE;
		$pagination_amount	= (!empty($params['pagination_amount'])) ? $params['pagination_amount']: 20;
		$pagination_start	= (!empty($params['pagination_start'])) ? $params['pagination_start']: 0;
		$sort				= (!empty($params['sort'])) ? $params['sort']: 'next_due_date';
		$sort_dir			= (!empty($params['sort_dir']) && $params['sort_dir'] == 'DESC') ? 'DESC': 'ASC';

		$join = false;
		$repeats = new transaction_repeat();
		if ($last_due_date == 'false') {
			$repeats->groupStart();
			$repeats->orWhere('last_due_date IS NULL', FALSE, FALSE);
			$repeats->orWhere('last_due_date >= now()', FALSE, FALSE);
			$repeats->groupEnd();
		}
		if ($name) {
			$repeats->join('vendor V1', 'V1.id = transaction_repeat.vendor_id', 'left');
			if (!$join) {
				$repeats->join('transaction_repeat_split', 'transaction_repeat.id = transaction_repeat_split.transaction_repeat_id', 'left');
				$repeats->join('vendor V2', 'V2.id = transaction_repeat_split.vendor_id', 'left');
				$join = true;
			}
			$repeats->groupStart();
			$repeats->orLike('V1.name', $name, 'both');
			$repeats->orLike('V2.name', $name, 'both');
			$repeats->groupEnd();
		}
		if ($bank_account_id) {
			$repeats->where('transaction_repeat.bank_account_id', $bank_account_id);
		}
		if ($amount) {
			if (!$join) {
				$repeats->join('transaction_repeat_split', 'transaction_repeat.id = transaction_repeat_split.transaction_repeat_id', 'left');
				$join = true;
			}
			$repeats->groupStart();
			$repeats->orWhere('transaction_repeat.amount', $amount);
			$repeats->orWhere('transaction_repeat_split.amount', $amount);
			$repeats->groupEnd();
		}
		if ($category_id) {
			if (!$join) {
				$repeats->join('transaction_repeat_split', 'transaction_repeat.id = transaction_repeat_split.transaction_repeat_id', 'left');
				$join = true;
			}
			$repeats->groupStart();
			$repeats->orWhere('transaction_repeat.category_id', $category_id);
			$repeats->orWhere('transaction_repeat_split.category_id', $category_id);
			$repeats->groupEnd();
		}
		$repeats->select('SQL_CALC_FOUND_ROWS transaction_repeat.*', FALSE);
		$repeats->where('transaction_repeat.is_deleted', 0);
		$repeats->limit($pagination_amount, $pagination_start);
		$repeats->orderBy($sort, $sort_dir);
		$repeats->result();

		$this->ajax->setData('total_rows', $repeats->foundRows());

		foreach ($repeats as $repeat) {
			isset($repeat->category);
			isset($repeat->vendor);
			isset($repeat->bank_account);
		}
		$this->ajax->setData('result', $repeats);
		$this->ajax->output();
	}

	public function edit() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (repeat/edit)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid repeat id - " . $id . " (repeat/edit)"));
			$this->ajax->output();
		}

		$repeat = new transaction_repeat($id);
		isset($repeat->category);
		isset($repeat->vendor);
		isset($repeat->bank_account);
		isset($repeat->repeats);
		if (!empty($repeat->splits)) {
			foreach ($repeat->splits as $split) {
				isset($split->vendor);
				isset($split->category);
			}
		}

		$this->ajax->setData('result', $repeat);

		$this->ajax->output();
	}

	public function save() {
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (repeat/save)"));
			$this->ajax->output();
		}

		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// VALIDATION
		$this->form_validation->set_rules('description', 'Description', 'required|max_length[100]');
		$this->form_validation->set_rules('category_id', 'Category', 'integer|callback_isValidCategory');
		$this->form_validation->set_rules('vendor_id', 'Vendor', 'integer|callback_isValidVendor');
		$this->form_validation->set_rules('bank_account_id', 'Account', 'integer|required');
		$this->form_validation->set_rules('first_due_date', 'First Due Date', 'required|callback_isValidDate');
		$this->form_validation->set_rules('last_due_date', 'Last Due Date', 'callback_isValidDate');
		$this->form_validation->set_rules('next_due_date', 'Next Due Date', 'required|callback_isValidDate');
		$this->form_validation->set_rules('type', 'Type', 'required|alpha');
		$this->form_validation->set_rules('every_unit', 'Every Unit', 'required|alpha');
		$this->form_validation->set_rules('every', 'Every', 'required|integer');
		$this->form_validation->set_rules('amount', 'Amount', 'required|number');

		// validate repeat data
		foreach ($_POST['repeats'] as $idx => $repeat) {
			if (empty($split['is_deleted']) || $split['is_deleted'] != 1) {
				$this->form_validation->set_rules('repeats[' . $idx . '][every_day]', 'Day', 'callback_isValidDay');
				$this->form_validation->set_rules('repeats[' . $idx . '][every_date]', 'Date', 'callback_isValidDayOfMonth');
				$this->form_validation->set_rules('repeats[' . $idx . '][every_month]', 'Month', 'callback_isValidMonth');
			}
		}

		// validate any split data
		if (!empty($_POST['splits'])) {
			foreach ($_POST['splits'] as $idx => $split) {
				if (empty($split['is_deleted']) || $split['is_deleted'] != 1) {
					$this->form_validation->set_rules('splits[' . $idx . '][amount]', 'Split Amount', 'required');
					$this->form_validation->set_rules('splits[' . $idx . '][type]', 'Split Type', 'required|alpha');
					$this->form_validation->set_rules('splits[' . $idx . '][category_id]', 'Split Category', 'required|integer');
					$this->form_validation->set_rules('splits[' . $idx . '][vendor_id]', 'Split Vendor', 'required|integer');
				}
			}
		}

		if ($this->form_validation->ajaxRun('') === FALSE) {
			$this->ajax->output();
		}

		$id = (!empty($_POST['id'])) ? $_POST['id']: NULL;
		$repeat = new transaction_repeat($id);
		$repeat->description		= $_POST['description'];
		$repeat->bank_account_id	= $_POST['bank_account_id'];
		$repeat->first_due_date		= $_POST['first_due_date'];
		$repeat->last_due_date		= (!empty($_POST['last_due_date'])) ? $_POST['last_due_date']: NULL;
		$repeat->next_due_date		= $_POST['next_due_date'];
		$repeat->type				= $_POST['type'];
		$repeat->amount				= $_POST['amount'];
		$repeat->every_unit			= $_POST['every_unit'];
		$repeat->every				= $_POST['every'];
		$repeat->notes				= (!empty($_POST['notes'])) ? $_POST['notes']: NULL;
		$repeat->vendor_id			= (empty($_POST['splits'])) ? $_POST['vendor_id']: NULL;	// ignore vendor_id if splits are present
		$repeat->category_id		= (empty($_POST['splits'])) ? $_POST['category_id']: NULL;	// ignore category if splits are present
		$repeat->exact_match		= (!empty($_POST['exact_match']) && $_POST['exact_match']) ? 1: 0;
		$repeat->save();

		foreach ($_POST['repeats'] as $repeat_every) {
			$id = (!empty($repeat_every['id'])) ? $repeat_every['id']: NULL;
			$transaction_repeat_every = new transaction_repeat_every($id);
			if (empty($repeat['is_deleted']) || $repeat['is_deleted'] != 1) {
				$transaction_repeat_every->transaction_repeat_id	= $repeat->id;
				$transaction_repeat_every->every_day				= (!empty($repeat_every['every_day'])) ? $repeat_every['every_day']: NULL;
				$transaction_repeat_every->every_date				= $repeat_every['every_date'];
				$transaction_repeat_every->every_month				= $repeat_every['every_month'];
				$transaction_repeat_every->save();
			} else {
				$transaction_repeat_every->delete();
			}
		}

		if (!empty($_POST['splits'])) {
			foreach ($_POST['splits'] as $split) {
				$transaction_repeat_split = new transaction_repeat_split($split['id']);
				if (empty($split['is_deleted']) || $split['is_deleted'] != 1) {
					$transaction_repeat_split->amount					= $split['amount'];
					$transaction_repeat_split->transaction_repeat_id	= $repeat->id;
					$transaction_repeat_split->type						= $split['type'];
					$transaction_repeat_split->category_id				= $split['category_id'];
					$transaction_repeat_split->vendor_id				= $split['vendor_id'];
					$transaction_repeat_split->notes					= (!empty($split['notes'])) ? $split['notes']: NULL;
					$transaction_repeat_split->save();
				} else {
					$transaction_repeat_split->delete();
				}
			}
		}
		$this->ajax->setdata('id', $repeat->id);
		$this->ajax->output();
	}
	
	/**
	 * Checks if a valid Day of week is entered
	 */
	public function isValidDay($day) {
		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);
		if ($_POST['every_unit'] == 'Week') {
			if (empty($day) || !is_numeric($day) || $day < 1 || $day > 7) {
				$this->form_validation->set_message('isValidDay', 'The Day of Week is Invalid');
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Checks if a valid Day of week is entered
	 */
	public function isValidDayOfMonth($date) {
		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);
		if ($_POST['every_unit'] == 'Month' || $_POST['every_unit'] == 'Year') {
			if (empty($date) || !is_numeric($date) || $date < 1 || $date > 31) {
				$this->form_validation->set_message('isValidDayOfMonth', 'The Day of Month is Invalid');
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Checks if a valid Day of week is entered
	 */
	public function isValidMonth($month) {
		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);
		if ($_POST['every_unit'] == 'Year') {
			if (empty($month) || !is_numeric($month) || $month < 1 || $month > 12) {
				$this->form_validation->set_message('isValidMonth', 'The Month is Invalid');
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Checks for a valid date
	 */
	public function isValidDate() {
		// TODO
		return TRUE;
	}

	/**
	 * Checks if splits are entered, if not main category is a required field
	 */
	public function isValidCategory() {
		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// if no splits then category is required otherwise MUST be NULL (will be ignored in Save)
		if (empty($_POST['splits']) && empty($_POST['category_id'])) {
			$this->form_validation->set_message('isValidCategory', 'The Category Field is Required');
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Checks if splits are entered, if not main vendor_id is a required field
	 */
	public function isValidVendor() {
		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// if no splits then vendor_id is required otherwise MUST be NULL (will be ignored in Save)
		if (empty($_POST['splits']) && empty($_POST['vendor_id'])) {
			$this->form_validation->set_message('isValidVendor', 'The Vendor Field is Required');
			return FALSE;
		}
		return TRUE;
	}

	public function delete() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (repeat/delete)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid repeat id - " . $id . " (repeat/delete)"));
			$this->ajax->output();
		}

		$repeat = new transaction_repeat($id);
		if ($repeat->numRows()) {
			$repeat->delete();
		} else {
			$this->ajax->addError(new AjaxError("Invalid repeat - (repeat/delete)"));
		}
		$this->ajax->output();
	}

}

// EOF