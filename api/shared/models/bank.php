<?php
/*
 * bank.php
 * Brian Markham 04/09/2015
 *
*/
class bank extends Nagilum {

	public $table = 'bank';

	public $hasMany = array('accounts' => array('class' => 'bank_account', 'joinField' => 'bank_id')
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