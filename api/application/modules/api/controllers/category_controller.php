<?php
/*
 * REST Category controller
 */

require_once ('rest_controller.php');

class category_controller Extends rest_controller
{
	protected $debug = TRUE;

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'GET')
		{
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (category/index)"));
			$this->ajax->output();
		}

		$categories = new category();
		$categories->whereNotDeleted();
		$categories->orderBy('order');
		$categories->result();

		$this->ajax->setData('categories', $categories);

		$this->ajax->output();
	}

	public function load()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'GET')
		{
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (category/load)"));
			$this->ajax->output();
		}

		$params = $this->input->get();

		$name				= (!empty($params['name'])) ? $params['name']: FALSE;
		$pagination_amount	= (!empty($params['pagination_amount'])) ? $params['pagination_amount']: 20;
		$pagination_start	= (!empty($params['pagination_start'])) ? $params['pagination_start']: 0;
		$sort				= (!empty($params['sort'])) ? $params['sort']: 'name';
		$sort_dir			= (!empty($params['sort_dir']) && $params['sort_dir'] == 'DESC') ? 'DESC': 'ASC';

		$categories = new category();
		if ($name)
		{
			$categories->like('name', $name);
		}
		$categories->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$categories->whereNotDeleted();
		$categories->limit($pagination_amount, $pagination_start);
		$categories->orderBy($sort, $sort_dir);
		$categories->result();
		if ($categories->numRows())
		{
			$this->ajax->setData('result', $categories);
		} else {
			$this->ajax->addError(new AjaxError("No categories found"));
		}
		$this->ajax->output();
	}

	public function edit()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'GET')
		{
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (category/edit)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0)
		{
			$this->ajax->addError(new AjaxError("Invalid category id - " . $id . " (category/edit)"));
			$this->ajax->output();
		}

		$category = new category($id);
		
		$this->ajax->setData('result', $category);

		$this->ajax->output();
	}

	public function save()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST')
		{
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (category/save)"));
			$this->ajax->output();
		}

		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// VALIDATION
		$this->form_validation->set_rules('name', 'Name', 'required');
		$this->form_validation->set_rules('order', 'Order', 'required|numeric');

		if ($this->form_validation->ajaxRun('') === FALSE) {
			$this->ajax->output();
		}

		$category = new category($_POST['id']);
		$category->name			= $_POST['name'];
		$category->description	= $_POST['description'];
		$category->order		= $_POST['order'];
		$category->save();

		$this->ajax->output();
	}

	public function delete()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'GET')
		{
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (category/delete)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0)
		{
			$this->ajax->addError(new AjaxError("Invalid category id - " . $id . " (category/delete)"));
			$this->ajax->output();
		}
		
		$category = new category($id);
		if ($category->numRows())
		{
			$category->delete();
		} else {
			$this->ajax->addError(new AjaxError("Invalid category - " . $id . " (category/delete)"));
		}
		$this->ajax->output();
	}

}

// EOF