<?php
/*
 * REST Vendor controller
 */

require_once ('rest_controller.php');

class vendor_controller Extends rest_controller {

	protected $debug = TRUE;

	public function __construct() {
		parent::__construct();
	}

	public function index() {
		$this->ajax->addError(new AjaxError("403 - Forbidden (vendor/index)"));
		$this->ajax->output();
	}

	public function get() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (vendor/loadAll)"));
			$this->ajax->output();
		}

		$params = $this->input->get();

		$name				= (!empty($params['name'])) ? $params['name']: FALSE;
		$pagination_amount	= (!empty($params['pagination_amount'])) ? $params['pagination_amount']: 20;
		$pagination_start	= (!empty($params['pagination_start'])) ? $params['pagination_start']: 0;
		$sort				= (!empty($params['sort'])) ? $params['sort']: 'vendor_date';
		$sort_dir			= (!empty($params['sort_dir']) && $params['sort_dir'] == 'DESC') ? 'DESC': 'ASC';

		$vendors = new vendor();
		if ($description) {
			$vendors->like('description', $description);
		}
		if ($name) {
			$vendors->where('name', $name);
		}
		$vendors->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$vendors->whereNotDeleted();
		$vendors->limit($pagination_amount, $pagination_start);
		$vendors->orderBy($sort, $sort_dir);
		$vendors->result();

		$this->ajax->setData('total_rows', $vendors->foundRows());

		if ($vendors->numRows()) {
			foreach ($vendors as $vendor) {

			}
			$this->ajax->setData('result', $vendors);
		} else {
			$this->ajax->addError(new AjaxError("No vendors found"));
		}
		$this->ajax->output();
	}

	public function edit() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (vendor/edit)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid vendor id - " . $id . " (vendor/edit)"));
			$this->ajax->output();
		}

		$vendor = new vendor($id);

		$this->ajax->setData('result', $vendor);

		$this->ajax->output();
	}

	public function save() {
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (vendor/save)"));
			$this->ajax->output();
		}

		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// VALIDATION
		$this->form_validation->set_rules('name', 'Name', 'required|max_length[200]');
		$this->form_validation->set_rules('description', 'Description', 'max_length[100]');
		$this->form_validation->set_rules('street', 'Street', 'max_length[100]');
		$this->form_validation->set_rules('city', 'City', 'max_length[50]');
		$this->form_validation->set_rules('state', 'State', 'max_length[2]');
		$this->form_validation->set_rules('phone_area_code', 'Area Code', 'numeric|exact_length[3]|callback_isAreaCodeValid');
		$this->form_validation->set_rules('phone_prefix', 'Prefix', 'numeric|exact_length[3]|callback_isPrefixValid');
		$this->form_validation->set_rules('phone_number', 'Number', 'numeric|exact_length[4]|callback_isNumberValid');

		if ($this->form_validation->ajaxRun('') === FALSE) {
			$this->ajax->output();
		}

		$vendor = new vendor($_POST['id']);
		$vendor->name				= $_POST['name'];
		$vendor->description		= $_POST['description'];
		$vendor->notes				= $_POST['notes'];
		$vendor->street				= $_POST['street'];
		$vendor->city				= $_POST['city'];
		$vendor->state				= $_POST['state'];
		$vendor->phone_area_code	= $_POST['phone_area_code'];
		$vendor->phone_prefix		= $_POST['phone_prefix'];
		$vendor->phone_number		= $_POST['phone_number'];
		$vendor->save();

		$this->ajax->setdata('id', $vendor->id);
		$this->ajax->output();
	}
	
	public function isAreaCodeValid() {
		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// if phone area code is entered make sure other phone fields are required
		if (!empty($_POST['phone_area_code']) || !empty($_POST['phone_prefix']) || !empty($_POST['phone_number'])) {
			if (empty($_POST['phone_area_code'])) {
				$this->form_validation->set_message('isAreaCodeValid', 'The Area Code Field is Required');
				return FALSE;
			}
		}
		return TRUE;
	}

	public function isPrefixValid() {
		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// if phone prefix is entered make sure other phone fields are required
		if (!empty($_POST['phone_area_code']) || !empty($_POST['phone_prefix']) || !empty($_POST['phone_number'])) {
			if (empty($_POST['phone_prefix'])) {
				$this->form_validation->set_message('isPrefixValid', 'The Prefix Field is Required');
				return FALSE;
			}
		}
		return TRUE;
	}

	public function isNumberValid() {
		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// if phone number is entered make sure other phone fields are required
		if (!empty($_POST['phone_area_code']) || !empty($_POST['phone_prefix']) || !empty($_POST['phone_number'])) {
			if (empty($_POST['phone_number'])) {
				$this->form_validation->set_message('isNumberValid', 'The Area Code Field is Required');
				return FALSE;
			}
		}
		return TRUE;
	}

	public function delete() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (vendor/delete)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0) {
			$this->ajax->addError(new AjaxError("Invalid vendor id - " . $id . " (vendor/delete)"));
			$this->ajax->output();
		}
		
		$vendor = new vendor($id);
		if ($vendor->numRows()) {
//			$vendor->delete();
		} else {
			$this->ajax->addError(new AjaxError("Invalid vendor - (vendor/delete)"));
		}
		$this->ajax->output();
	}

}

// EOF