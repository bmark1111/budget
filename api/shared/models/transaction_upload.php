<?php
/*
 * transaction_upload.php
 * Brian Markham 05/06/2015
 *
*/
class transaction_upload extends Nagilum {

	public $table = 'transaction_upload';
	
	public $hasOne = array(	'bank_account' => array('class' => 'bank_account', 'joinField' => 'bank_account_id')
						);

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = FALSE) {
		parent::__construct($id);
	}

}
//EOF