<?php
/*
 * vendor.php
 * Brian Markham 03/04/2016
 *
*/
//class vendor extends Nagilum {
//
//	public $table = 'vendor';
//	
//	public $hasOne = array(	//'transactions' => array('class' => 'transaction', 'joinField' => 'transaction_id'),
//							//'category' => array('class' => 'category', 'joinField' => 'category_id'),
//							//'bank_account' => array('class' => 'bank_account', 'joinField' => 'bank_account_id')
//						);
//	public $hasMany = array(//'repeats' => array('class' => 'transaction_repeat_every', 'joinField' => 'transaction_repeat_id'),
//							//'splits' => array('class' => 'transaction_repeat_split', 'joinField' => 'transaction_repeat_id')
//						);
//
//	public $autoPopulateHasOne = FALSE;
//	public $autoPopulateHasMany = FALSE;
//
//	public function __construct($id = FALSE) {
//		parent::__construct($id);
//	}
//
//	public function postResultHook() {
//		parent::postResultHook();
//
//		$displayName = array($this->name);
//		if ($this->street) {
//			$displayName[] = ($this->city || $this->state) ? $this->street . ',': $this->street;
//		}
//		if ($this->city) {
//			$displayName[] = $this->city;
//		}
//		if ($this->state) {
//			$displayName[] = $this->state;
//		}
//		$this->display_name = implode(' ', $displayName);
//
////		unset($this->street);
////		unset($this->city);
////		unset($this->state);
////		unset($this->phone_area_code);
////		unset($this->phone_prefix);
////		unset($this->phone_number);
////		unset($this->description);
////		unset($this->notes);
//		unset($this->is_deleted);
//		unset($this->created_by);
//		unset($this->created_at);
//		unset($this->updated_by);
//		unset($this->updated_at);
//  	}
//
//	public function postSaveHook() {
//		parent::postSaveHook();
//
//		$displayName = array($this->name);
//		if ($this->street) {
//			$displayName[] = ($this->city || $this->state) ? $this->street . ',': $this->street;
//		}
//		if ($this->city) {
//			$displayName[] = $this->city;
//		}
//		if ($this->state) {
//			$displayName[] = $this->state;
//		}
//		$this->display_name = implode(' ', $displayName);
//	}
//}
(defined('BASEPATH')) OR exit('No direct script access allowed');

require (SHAREPATH . "models/vendor.php");
//EOF