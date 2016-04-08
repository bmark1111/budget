<?php

class upload_controller extends EP_Controller {

	public function __construct() {
		parent::__construct();
	}

	public function index($bank_account_id = FALSE, $ignoreFirstLine = FALSE) {
		$config = array();
//		$config['upload_path'] = '../uploads/';
		$config['upload_path'] = '../../../public_ftp/';
		$config['allowed_types'] = 'csv';
		$config['max_size']	= '1000';
		$config['overwrite'] = TRUE;

		$this->load->library('upload', $config);

		if (!$this->upload->do_upload('file')) {
			echo json_encode(array('success' => 0, 'errors' => $this->upload->error_msg));
		} else {
			$upload_datetime = date('Y-m-d H:i:s');

			$file_handle = fopen($config['upload_path'] . $this->upload->file_name, "r");
			while (!feof($file_handle)) {
				$params = fgetcsv($file_handle);
				if ($ignoreFirstLine != 1) {
					if (count($params) == 5) {
						$transaction = new transaction_upload();
						$transaction->upload_datetime	= $upload_datetime;
// bug in downloaded/imported file from chase
if ($params[0] == 'DEBIT' && floatval($params[3]) >= 0) {
	$params[0] = 'CREDIT';
} elseif ($params[0] == 'CREDIT' && floatval($params[3]) < 0) {
	$params[0] = 'DEBIT';
}
						$transaction->type				= $params[0];
						$transaction->transaction_date	= date('Y-m-d', strtotime($params[1]));
						$transaction->description		= ltrim(rtrim($params[2], '"'), '"');
						$transaction->amount			= (floatval($params[3]) < 0) ? -floatval($params[3]): floatval($params[3]);
						$transaction->check_num			= (!empty($params[4])) ? $params[4]: NULL;
						$transaction->bank_account_id	= $bank_account_id;
						$transaction->save();
					}
				}
				$ignoreFirstLine = FALSE;
			}

			// get count of uploaded transactions
			$transactions = new transaction_upload();
			$transactions->select('count(*) as count');
			$transactions->whereNotDeleted();
			$transactions->where('status', 0);
			$transactions->row();

			fclose($file_handle);
			echo json_encode(array('success' => 1, 'count' => $transactions->count));
		}
	}

}

// EOF