<?php
/*
 * transaction_repeat_split.php
 * Brian Markham 03/04/2016
 *
*/
class transaction_repeat_split extends Nagilum {

	public $table = 'transaction_repeat_split';
	
	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = FALSE) {
		parent::__construct($id);
	}

}
//EOF