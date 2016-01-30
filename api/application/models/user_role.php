<?php
/*
 * user_role.php
 * Brian Markham 05/21/2015
 *
*/
class user_role extends Nagilum {

	public $table = 'user_role';

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = NULL) {
		parent::__construct($id);
	}

}

// EOF