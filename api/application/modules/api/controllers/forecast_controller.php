<?php
/*
 * REST Forecast controller
 */

require_once ('rest_controller.php');

class forecast_controller Extends rest_controller
{
	protected $debug = TRUE;

	public function index() {
//		$this->ajax->set_header("Forbidden", '403');
		$this->ajax->addError(new AjaxError("403 - Forbidden (forecast/index)"));
		$this->ajax->output();
	}

	public function loadAll() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (forecast/loadAll)"));
			$this->ajax->output();
		}

		$params = $this->input->get();
		$this->_loadAll($params);
	}
	
	private function _loadAll($params) {
		$first_due_date		= (!empty($params['first_due_date'])) ? $params['first_due_date']: FALSE;
		$description		= (!empty($params['description'])) ? $params['description']: FALSE;
		$amount				= (!empty($params['amount'])) ? $params['amount']: FALSE;
		$pagination_amount	= (!empty($params['pagination_amount'])) ? $params['pagination_amount']: 20;
		$pagination_start	= (!empty($params['pagination_start'])) ? $params['pagination_start']: 0;
		$sort				= (!empty($params['sort'])) ? $params['sort']: 'first_due_date';
		$sort_dir			= (!empty($params['sort_dir']) && $params['sort_dir'] == 'DESC') ? 'DESC': 'ASC';

		$forecasts = new forecast();
		if ($first_due_date) {
			$first_due_date = date('Y-m-d', strtotime($first_due_date));
			$forecasts->where('first_due_date', $first_due_date);
		}
		if ($description) {
			$forecasts->like('description', $description);
		}
		if ($amount) {
			$forecasts->where('amount', $amount);
		}
		$forecasts->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$forecasts->whereNotDeleted();
		$forecasts->limit($pagination_amount, $pagination_start);
		$forecasts->orderBy($sort, $sort_dir);
		$forecasts->result();

		$this->ajax->setData('total_rows', $forecasts->foundRows());

		if ($forecasts->numRows()) {
			foreach ($forecasts as $forecast) {
				isset($forecast->category);
				isset($forecast->bank_account);
			}
			$this->ajax->setData('result', $forecasts);
		} else {
			$this->ajax->addError(new AjaxError("No forecasts found"));
		}
		$this->ajax->output();
	}

	public function edit() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (forecast/edit)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid forecast id - " . $id . " (forecast/edit)"));
			$this->ajax->output();
		}

		$forecast = new forecast($id);

		$this->ajax->setData('result', $forecast);

		$this->ajax->output();
	}

	public function save() {
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (forecast/save)"));
			$this->ajax->output();
		}

		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// VALIDATION
		$this->form_validation->set_rules('bank_account_id', 'Bank Account', 'required');
		$this->form_validation->set_rules('first_due_date', 'Date', 'required');
		$this->form_validation->set_rules('description', 'Description', 'required|max_length[150]');
		$this->form_validation->set_rules('type', 'Type', 'required|alpha');
		$this->form_validation->set_rules('every', 'Every', 'required');
		$this->form_validation->set_rules('every_unit', 'Unit', 'required');
		$this->form_validation->set_rules('category_id', 'Category', 'required');
		$this->form_validation->set_rules('amount', 'Amount', 'required');

		if ($this->form_validation->ajaxRun('') === FALSE) {
			$this->ajax->output();
		}

		$forecast = new forecast($_POST['id']);
		$forecast->first_due_date	= date('Y-m-d', strtotime($_POST['first_due_date']));
		$forecast->last_due_date	= (!empty($_POST['last_due_date'])) ? date('Y-m-d', strtotime($_POST['last_due_date'])): NULL;
		$forecast->description		= $_POST['description'];
		$forecast->type				= $_POST['type'];
		$forecast->every			= $_POST['every'];
		$forecast->every_unit		= $_POST['every_unit'];
		$forecast->every_on			= $_POST['every_on'];
		$forecast->category_id		= $_POST['category_id'];
		$forecast->amount			= $_POST['amount'];
		$forecast->notes			= $_POST['notes'];
		$forecast->save();

		$this->ajax->output();
	}

	public function delete() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (forecast/delete)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid forecast id - " . $id . " (forecast/delete)"));
			$this->ajax->output();
		}
		
		$forecast = new forecast($id);
		if ($forecast->numRows()) {
			$forecast->delete();
		} else {
			$this->ajax->addError(new AjaxError("Invalid forecast - (forecast/delete)"));
		}
		$this->ajax->output();
	}

	public function this() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (forecast/this)"));
			$this->ajax->output();
		}

		$interval_beginning	= $this->input->get('interval_beginning');
		if (!$interval_beginning || !strtotime($interval_beginning)) {
			$this->ajax->addError("Invalid week beginning - forecast/this");
			$this->ajax->output();
		}
		$interval_beginning = explode('T', $interval_beginning);
		$sd = date('Y-m-d', strtotime($interval_beginning[0]));
		$ed = date('Y-m-d', strtotime($sd . ' +' . $this->budget_interval . ' ' . $this->budget_interval_unit));

		$category_id	= $this->input->get('category_id');
		if ($category_id == 0 || !is_numeric($category_id)) {
			$this->ajax->addError("Invalid category id - forecast/this");
			$this->ajax->output();
		}

		$forecast = new forecast();
		$forecast->whereNotDeleted();
		$forecast->where('category_id', $category_id);
		$forecast->result();
		if ($forecast->numRows()) {
			$x = 0;
			$transactions = array();

			// set the next due date(s) for the forecasted expenses
			foreach ($forecast as $fc) {
				// initialize the offset
				$offset = 0;
				switch ($fc->every_unit) {
					case 'Days':
						while (strtotime($sd . " +" . $offset . " Days") < strtotime($ed) && (!$fc->last_due_date || strtotime($sd . " +" . $offset . " Days") <= strtotime($fc->last_due_date))) {
							if (strtotime($sd . " +" . $offset . " Days") >= strtotime($sd) && strtotime($sd . " +" . $offset . " Days") >= strtotime($fc->first_due_date)) {
								$transactions[$x]['transaction_date']	= date('Y-m-d', strtotime($sd . " +" . $offset . " Days"));
								$transactions[$x]['amount']				= $fc->amount;
								$transactions[$x]['description']		= $fc->description;
								$x++;
							}
							$offset += $fc->every;
						}
						break;
					case 'Weeks':
						while (strtotime($sd . " +" . $offset . " Weeks") < strtotime($ed) && (!$fc->last_due_date || strtotime($sd . " +" . $offset . " Weeks") <= strtotime($fc->last_due_date))) {
							if (strtotime($sd . " +" . $offset . " Weeks") >= strtotime($sd) && strtotime($sd . " +" . $offset . " Weeks") >= strtotime($fc->first_due_date)) {
								$transactions[$x]['transaction_date']	= date('Y-m-d', strtotime($sd . " +" . $offset . " Weeks"));
								$transactions[$x]['amount']				= $fc->amount;
								$transactions[$x]['description']		= $fc->description;
								$x++;
							}
							$offset += $fc->every;;
						}
						break;
					case 'Months':
						$dt = explode('-', $sd);
						$date = $dt[0] . '-' . $dt[1] . '-' . $fc->every_on;
						while (strtotime($date . " +" . $offset . " Months") < strtotime($ed) && (!$fc->last_due_date || strtotime($sd . " +" . $offset . " Months") <= strtotime($fc->last_due_date))) {
							if (strtotime($date . " +" . $offset . " Months") >= strtotime($sd) && strtotime($date . " +" . $offset . " Months") >= strtotime($fc->first_due_date)) {
								$transactions[$x]['transaction_date']	= date('Y-m-d', strtotime($date . " +" . $offset . " Months"));
								$transactions[$x]['amount']				= $fc->amount;
								$transactions[$x]['description']		= $fc->description;
								$x++;
							}
							$offset += $fc->every;
						}
						break;
					case 'Years':
						$dt = explode('-', $sd);
						$date = $dt[0] . '-' . $fc->every_on;
						while (strtotime($date . " +" . $offset . " Years") < strtotime($ed) && (!$fc->last_due_date || strtotime($sd . " +" . $offset . " Years") <= strtotime($fc->last_due_date))) {
							if (strtotime($date . " +" . $offset . " Years") >= strtotime($sd) && strtotime($date . " +" . $offset . " Years") >= strtotime($fc->first_due_date)) {
								$transactions[$x]['transaction_date']	= date('Y-m-d', strtotime($date . " +" . $offset . " Years"));
								$transactions[$x]['amount']				= $fc->amount;
								$transactions[$x]['description']		= $fc->description;
								$x++;
							}
							$offset += $fc->every;
						}
						break;
				}
			}
			$this->ajax->setData('result', $transactions);
		} else {
			$this->ajax->addError("Error - No forecast found");
		}
		$this->ajax->output();
	}

}

// EOF