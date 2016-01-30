<?php
/**
 * forecast.php
 * Brian Markham 04/10/2015
 *
*/
class forecast extends Nagilum {
	public $table = 'forecast';
	
	public $hasOne = array(	'category' => array('class' => 'category', 'joinField' => 'category_id'),
							'bank_account' => array('class' => 'bank_account', 'joinField' => 'bank_account_id')
						);

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = FALSE) {
		parent::__construct($id);
	}

}

//EOF