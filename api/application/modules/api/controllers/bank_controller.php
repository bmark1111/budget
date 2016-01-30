<?php
/*
 * REST Bank controller
 */

require_once ('rest_controller.php');

class bank_controller Extends rest_controller
{
	protected $debug = TRUE;

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
//		$this->ajax->set_header("Forbidden", '403');
		$this->ajax->addError(new AjaxError("403 - Forbidden (bank/index)"));
		$this->ajax->output();
	}

	public function load()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'GET')
		{
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (bank/load)"));
			$this->ajax->output();
		}

		$params = $this->input->get();

		$name				= (!empty($params['name'])) ? $params['name']: FALSE;
		$pagination_amount	= (!empty($params['pagination_amount'])) ? $params['pagination_amount']: 20;
		$pagination_start	= (!empty($params['pagination_start'])) ? $params['pagination_start']: 0;
		$sort				= (!empty($params['sort'])) ? $params['sort']: 'name';
		$sort_dir			= (!empty($params['sort_dir']) && $params['sort_dir'] == 'DESC') ? 'DESC': 'ASC';

		$banks = new bank();
		if ($name)
		{
			$banks->like('name', $name);
		}
		$banks->select('SQL_CALC_FOUND_ROWS *', FALSE);
		$banks->whereNotDeleted();
		$banks->limit($pagination_amount, $pagination_start);
		$banks->orderBy($sort, $sort_dir);
		$banks->result();
		if ($banks->numRows())
		{
			isset($bank_account->bank);

			$this->ajax->setData('result', $banks);
		} else {
			$this->ajax->addError(new AjaxError("No banks found"));
		}
		$this->ajax->output();
	}

	public function edit()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'GET')
		{
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (bank/edit)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0)
		{
			$this->ajax->addError(new AjaxError("Invalid bank id - " . $id . " (bank/edit)"));
			$this->ajax->output();
		}

		$bank = new bank($id);
		isset($bank->accounts);
		
		$this->ajax->setData('result', $bank);

		$this->ajax->output();
	}

	public function save()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'POST')
		{
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (bank/save)"));
			$this->ajax->output();
		}

		$input = file_get_contents('php://input');
		$_POST = json_decode($input, TRUE);

		// VALIDATION
		$this->form_validation->set_rules('name', 'Bank Name', 'required');
		$this->form_validation->set_rules('accounts[0][id]', 'Accounts', 'required');

		// validate account data
		foreach ($_POST['accounts'] as $idx => $account)
		{
			if (empty($account['is_deleted']) || $account['is_deleted'] != 1)
			{
				$this->form_validation->set_rules('accounts[' . $idx . '][name]', 'Name', 'required');
				$this->form_validation->set_rules('accounts[' . $idx . '][balance]', 'Balance', 'required');
			}
		}

		if ($this->form_validation->ajaxRun('') === FALSE)
		{
			$this->ajax->output();
		}

		$bank = new bank($_POST['id']);
		$bank->name	= $_POST['name'];
		$bank->save();

		foreach ($_POST['accounts'] as $account)
		{
			$bank_account = new bank_account($account['id']);
			if (empty($account['is_deleted']) || $account['is_deleted'] != 1)
			{
				$bank_account->bank_id		= $bank->id;
				$bank_account->name			= $account['name'];
				$bank_account->date_opened	= $account['date_opened'];
				$bank_account->date_closed	= $account['date_closed'];
		//		$bank_account->balance		= $account['balance'];
				$bank_account->save();
			} else {
				$bank_account->delete();
			}
		}

		$this->ajax->output();
	}

	public function delete()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'GET')
		{
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (bank/delete)"));
			$this->ajax->output();
		}

		$id = $this->input->get('id');
		if (!is_numeric($id) || $id <= 0)
		{
			$this->ajax->addError(new AjaxError("Invalid bank id - " . $id . " (bank/delete)"));
			$this->ajax->output();
		}
		
		$bank = new bank($id);
		if ($bank->numRows())
		{
			if (!empty($bank->accounts))
			{
				foreach ($bank->accounts as $account)
				{
					$account->delete();
				}
			}
			$bank->delete();
		} else {
			$this->ajax->addError(new AjaxError("Invalid bank - " . $id . " (bank/delete)"));
		}
		$this->ajax->output();
	}

	public function accounts()
	{
		if ($_SERVER['REQUEST_METHOD'] != 'GET')
		{
//			$this->ajax->set_header("Forbidden", '403');
			$this->ajax->addError(new AjaxError("403 - Forbidden (bank/accounts)"));
			$this->ajax->output();
		}

		$bank_accounts = new bank_account();
		$bank_accounts->whereNotDeleted();
		$bank_accounts->where('date_closed IS NULL', FALSE, FALSE);
		$bank_accounts->orderBy('name', 'ASC');
		$bank_accounts->result();
		foreach ($bank_accounts as $bank_account)
		{
			isset($bank_account->bank);
		}

		$this->ajax->setData('bank_accounts', $bank_accounts);

		$this->ajax->output();
	}

}

// EOF