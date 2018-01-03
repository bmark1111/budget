<?php
/**
 * @module account.php
 * Brian Markham 12/27/2015
 *
*/
class account extends Nagilum {

	protected $table = 'account';

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = NULL) {
		parent::__construct($id);
	}

}

// EOF