<?php
/*
 * transaction_repeat.php
 * Brian Markham 05/29/2016
 *
*/
class transaction_repeat extends Nagilum {

	public $table = 'transaction_repeat';
	
	public $hasOne = array(	//'transactions' => array('class' => 'transaction', 'joinField' => 'transaction_id'),
							'category' => array('class' => 'category', 'joinField' => 'category_id'),
							'bank_account' => array('class' => 'bank_account', 'joinField' => 'bank_account_id'),
							'vendor' => array('class' => 'vendor', 'joinField' => 'vendor_id')
						);
	public $hasMany = array(//'repeats' => array('class' => 'transaction_repeat_every', 'joinField' => 'transaction_repeat_id')
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