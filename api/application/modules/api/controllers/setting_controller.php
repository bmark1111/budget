<?php
/*
 * REST Setting controller
 */

require_once ('rest_controller.php');

class setting_controller Extends rest_controller {

	protected $debug = TRUE;

	public function __construct() {
		parent::__construct();
	}

	public function index() {
//		$this->ajax->set_header("Forbidden", '403');
		$this->ajax->addError(new AjaxError("403 - Forbidden (setting/index)"));
		$this->ajax->output();
	}
	
	public function load() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (setting/load)"));
			$this->ajax->output();
		}

		$setting = new setting();
		$setting->select('id, description, name, value, type, options');
		$setting->whereNotDeleted();
		$setting->result();
		$this->ajax->setData('settings', $setting);

		$this->ajax->output();
	}

	public function save() {
		if ($_SERVER['REQUEST_METHOD'] != 'POST') {
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (setting/save)"));
			$this->ajax->output();
		}

		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// VALIDATION
		if (empty($_POST['settings'])) {
			$this->ajax->addError(new AjaxError("Invalid data (setting/save)"));
			$this->ajax->output();
		}

		foreach ($_POST['settings'] as $idx => $setting) {
			$this->form_validation->set_rules('settings[' . $idx . '][description]', 'Description', 'required|max_length[100]');
			$this->form_validation->set_rules('settings[' . $idx . '][name]', 'Name', 'required|alphanumeric');
			$this->form_validation->set_rules('settings[' . $idx . '][value]', 'Value', 'required');
			$this->form_validation->set_rules('settings[' . $idx . '][type]', 'Type', 'required|integer');
		}

		if ($this->form_validation->ajaxRun('') === FALSE) {
			$this->ajax->output();
		}

		foreach ($_POST['settings'] as $new_setting) {
			$setting = new setting($new_setting['id']);
			$setting->description	= $new_setting['description'];
			$setting->name			= $new_setting['name'];
			$setting->type			= $new_setting['type'];
			switch ($new_setting['type']) {
				case '0':
					$setting->value = $new_setting['value'];
					break;
				case '1':
					$setting->value = $new_setting['value']['name'];
					break;
				case '2':
					$setting->value = date('Y-m-d', strtotime($new_setting['value']));
					break;
			}
			$setting->save();
		}

		$this->ajax->output();
	}

}

// EOF