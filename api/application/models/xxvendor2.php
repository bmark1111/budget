<?php
/*
 * vendor2.php
 * Brian Markham 03/04/2016
 *
*/
class vendor2 extends Nagilum {

	public $table = 'vendor2';
	
	public $hasOne = array(	//'transactions' => array('class' => 'transaction', 'joinField' => 'transaction_id'),
							//'category' => array('class' => 'category', 'joinField' => 'category_id'),
							//'bank_account' => array('class' => 'bank_account', 'joinField' => 'bank_account_id')
						);
	public $hasMany = array(//'repeats' => array('class' => 'transaction_repeat_every', 'joinField' => 'transaction_repeat_id'),
							//'splits' => array('class' => 'transaction_repeat_split', 'joinField' => 'transaction_repeat_id')
						);

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = FALSE) {
		parent::__construct($id);
	}

}
//EOF