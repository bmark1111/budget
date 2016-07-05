<?php
/*
 * transaction_split.php
 * Brian Markham 04/04/2015
 *
*/
class transaction_split extends Nagilum {

	public $table = 'transaction_split';
	
	public $hasOne = array(	'category' => array('class' => 'category', 'joinField' => 'category_id'),
							'vendor' => array('class' => 'vendor', 'joinField' => 'vendor_id')
						);

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = FALSE) {
		parent::__construct($id);
	}

}
//EOF