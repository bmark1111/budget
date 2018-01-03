<?php
/*
 * transaction_repeat_split.php
 * Brian Markham 03/04/2016
 *
*/
class transaction_repeat_split extends Nagilum {

	public $table = 'transaction_repeat_split';
	
	public $hasOne = array(	//'transactions' => array('class' => 'transaction', 'joinField' => 'transaction_id'),
							'category' => array('class' => 'category', 'joinField' => 'category_id'),
							'vendor' => array('class' => 'vendor', 'joinField' => 'vendor_id')
						);

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = FALSE) {
		parent::__construct($id);
	}

}
//EOF