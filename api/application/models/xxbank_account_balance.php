<?php
/*
 * bank_account_balance.php
 * Brian Markham 09/10/2015
 *
*/
class bank_account_balance extends Nagilum
{
	public $table = 'bank_account_balance';

	public $hasOne = array('bank_account' => array('class' => 'bank', 'joinField' => 'bank_account_id')
						);

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = FALSE)
	{
		parent::__construct($id);
	}

}
//EOF