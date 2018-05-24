<?php
/*
 * REST Live Search controller
 */

require_once ('rest_controller.php');

class livesearch_controller Extends rest_controller {

	protected $debug = TRUE;

	public function __construct() {
		parent::__construct();
	}

	public function index() {
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->ajax->addError(new AjaxError("403 - Forbidden (livesearch/index)"));
			$this->ajax->output();
		}

		$params = $this->input->get();

		switch ($params['type']) {
			case 'vendors':
				$vendors = new vendor();
				$vendors->like('name', $params['search'], 'both');
				$vendors->whereNotDeleted();
				$vendors->limit(20);
				$vendors->orderBy('name', 'asc');
				$vendors->result();
				$this->ajax->setData('result', $vendors);
				break;
		}
		$this->ajax->output();
	}

}

// EOF