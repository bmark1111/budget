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