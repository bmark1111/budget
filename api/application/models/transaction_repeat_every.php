<?php
/*
 * transaction_repeat_every.php
 * Brian Markham 04/04/2015
 *
*/
class transaction_repeat_every extends Nagilum {

	public $table = 'transaction_repeat_every';
	
	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = FALSE) {
		parent::__construct($id);
	}

}
//EOF