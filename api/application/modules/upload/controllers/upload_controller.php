<?php

class upload_controller extends EP_Controller {

	public function __construct() {
		parent::__construct();
	}

	public function index($account_id = FALSE, $ignoreFirstLine = FALSE) {
		$config = array();
		$config['upload_path'] = '../../../public_ftp/';
		$config['allowed_types'] = 'csv';
		$config['max_size']	= '1000';
		$config['overwrite'] = TRUE;

		$this->load->library('upload', $config);

		if (!$this->upload->do_upload('file')) {
			echo json_encode(array('success' => 0, 'errors' => $this->upload->error_msg));
		} else {
			// get upload map
			$upload_map = new upload_map();
			$upload_map->whereNotDeleted();
			$upload_map->where('account_id', $account_id);
			$upload_map->orderBy('offset', 'ASC');
			$upload_map->result();

			$upload_datetime = date('Y-m-d H:i:s');

			$file_handle = fopen($config['upload_path'] . $this->upload->file_name, "r");
			while (!feof($file_handle)) {
				$params = fgetcsv($file_handle);
//var_dump($params);
				if ($params !== false) {
					if ($ignoreFirstLine != 1) {
						$transaction = new transaction_upload();
						$transaction->upload_datetime	= $upload_datetime;
						$transaction->bank_account_id	= $account_id;
// bug in downloaded/imported file from chase bank
if ($params[0] === 'DEBIT' && floatval($params[3]) >= 0) {
	$params[0] = 'CREDIT';
} elseif ($params[0] === 'CREDIT' && floatval($params[3]) < 0) {
	$params[0] = 'DEBIT';
}
						foreach($upload_map as $map) {
							switch ($map->type) {
								case 'TEXT':
									$transaction[$map->field] = ltrim(rtrim($params[$map->offset], '"'), '"');
									break;
								case 'DATE':
									$transaction[$map->field] = date('Y-m-d', strtotime($params[$map->offset]));
									break;
								case 'AMOUNT1':
									$transaction->type = (floatval($params[$map->offset]) < 0) ? 'DEBIT': 'CREDIT';
									$transaction[$map->field] = (floatval($params[$map->offset]) < 0) ? -floatval($params[$map->offset]): floatval($params[$map->offset]);
									break;
								case 'AMOUNT2':
									$transaction->type = (floatval($params[$map->offset]) < 0) ? 'CREDIT': 'DEBIT';
									$transaction[$map->field] = (floatval($params[$map->offset]) < 0) ? -floatval($params[$map->offset]): floatval($params[$map->offset]);
									break;
								case 'DEBIT':
									if (strlen($params[$map->offset]) > 0) {
										$transaction->type = 'DEBIT';
										$transaction[$map->field] = floatval($params[$map->offset]);
									}
									break;
								case 'CREDIT':
									if (strlen($params[$map->offset]) > 0) {
										$transaction->type = 'CREDIT';
										$transaction[$map->field] = floatval($params[$map->offset]);
									}
									break;
							}
						}
//print $transaction;
						$transaction->save();
					}
				}
				$ignoreFirstLine = FALSE;
			}
//die('XXXXXXXXXXXXX');

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
//INSERT INTO `upload_map` (`bank_account_id`, `offet`, `field`, `is_deleted`, `created_by`, `created_at`, `updated_by`, `updated_at`) VALUES
//(6, 0, 'type', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-17 22:33:20'),
//(6, 1, 'transaction_date', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-17 22:33:22'),
//(6, 2, 'description', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-17 22:34:05'),
//(6, 3, 'amount', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-17 22:34:05'),
//(6, 4, 'check_num', 0, 1, '2016-10-17 00:00:00', 1, '2016-10-17 22:34:34');

//	public function index($account_id = FALSE, $ignoreFirstLine = FALSE) {
//		$config = array();
//		$config['upload_path'] = '../../../public_ftp/';
//		$config['allowed_types'] = 'csv';
//		$config['max_size']	= '1000';
//		$config['overwrite'] = TRUE;
//
//		$this->load->library('upload', $config);
//
//		if (!$this->upload->do_upload('file')) {
//			echo json_encode(array('success' => 0, 'errors' => $this->upload->error_msg));
//		} else {
//			$upload_datetime = date('Y-m-d H:i:s');
//
//			$file_handle = fopen($config['upload_path'] . $this->upload->file_name, "r");
//			while (!feof($file_handle)) {
//				$params = fgetcsv($file_handle);
//				if ($ignoreFirstLine != 1) {
//					if (count($params) == 5) {
//						$transaction = new transaction_upload();
//						$transaction->upload_datetime	= $upload_datetime;
//						$transaction->bank_account_id	= $account_id;
//// bug in downloaded/imported file from chase bank
//if ($params[0] == 'DEBIT' && floatval($params[3]) >= 0) {
//	$params[0] = 'CREDIT';
//} elseif ($params[0] == 'CREDIT' && floatval($params[3]) < 0) {
//	$params[0] = 'DEBIT';
//}
//						$transaction->type				= $params[0];
//						$transaction->transaction_date	= date('Y-m-d', strtotime($params[1]));
//						$transaction->description		= ltrim(rtrim($params[2], '"'), '"');
//						$transaction->amount			= (floatval($params[3]) < 0) ? -floatval($params[3]): floatval($params[3]);
//						$transaction->check_num			= (!empty($params[4])) ? $params[4]: NULL;
//						$transaction->save();
//					}
//				}
//				$ignoreFirstLine = FALSE;
//			}
//
//			// get count of uploaded transactions
//			$transactions = new transaction_upload();
//			$transactions->select('count(*) as count');
//			$transactions->whereNotDeleted();
//			$transactions->where('status', 0);
//			$transactions->row();
//
//			fclose($file_handle);
//			echo json_encode(array('success' => 1, 'count' => $transactions->count));
//		}
//	}

}

// EOF