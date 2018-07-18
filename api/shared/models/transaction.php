<?php
/*
 * transaction.php
 * Brian Markham 04/04/2015
 *
*/
class transaction extends Nagilum {

	public $table = 'transaction';
	
	public $hasOne = array(	'category' => array('class' => 'category', 'joinField' => 'category_id'),
							'bank_account' => array('class' => 'bank_account', 'joinField' => 'bank_account_id'),
						//	'repeat' => array('class' => 'transaction_repeat', 'joinField' => 'transaction_repeat_id'),
							'vendor' => array('class' => 'vendor', 'joinField' => 'vendor_id')
						);

	public $hasMany = array('splits' => array('class' => 'transaction_split', 'joinField' => 'transaction_id')
						);

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = FALSE) {
		parent::__construct($id);
	}

	public function postResultHook() {
		parent::postResultHook();

		unset($this->is_deleted);
		unset($this->created_by);
		unset($this->created_at);
		unset($this->updated_by);
		unset($this->updated_at);
  	}
}
//EOF