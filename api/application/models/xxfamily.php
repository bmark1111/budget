<?php
/*
 * family.php
 * Brian Markham 04/03/2015
 *
*/
class family extends Nagilum
{
	public $table = 'family';
	
//	public $hasOne = array(	'contact' => array('class' => 'contact', 'joinField' => 'contact_id')
//						);
//	public $hasMany = array('account_contacts' => array('class' => 'account_contact', 'joinField' => 'account_id')
//						);

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct()
	{
		parent::__construct();
	}

}
//EOF