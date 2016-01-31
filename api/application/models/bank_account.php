<?php
/*
 * bank_account.php
 * Brian Markham 04/09/2015
 *
*/
class bank_account extends Nagilum {

	public $table = 'bank_account';

	public $hasOne = array('bank' => array('class' => 'bank', 'joinField' => 'bank_id')
						);

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = FALSE) {
		parent::__construct($id);
	}

}
//EOF