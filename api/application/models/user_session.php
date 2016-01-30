<?php
/*
 * user_session.php
 * Brian Markham 05/21/2015
 *
*/
class user_session extends Nagilum {

	protected $table = 'user_session';
	protected $primaryKey = FALSE;

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = NULL) {
		parent::__construct($id);
	}

}

// EOF