<?php
/*
 * bank_account.php
 * Brian Markham 04/09/2015
 *
*/
//class bank_account extends Nagilum {
//
//	public $table = 'bank_account';
//
//	public $hasOne = array('bank' => array('class' => 'bank', 'joinField' => 'bank_id'),
//							'balance' => array('class' => 'transaction', 'joinField' => 'balance_transaction_id')
//						);
//
//	public $autoPopulateHasOne = FALSE;
//	public $autoPopulateHasMany = FALSE;
//
//	public function __construct($id = FALSE) {
//		parent::__construct($id);
//	}
//
//}
(defined('BASEPATH')) OR exit('No direct script access allowed');

require (SHAREPATH . "models/bank_account.php");
//EOF