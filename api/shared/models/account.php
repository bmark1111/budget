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

	public function postResultHook() {
		parent::postResultHook();

		unset($this->is_deleted);
		unset($this->created_by);
		unset($this->created_at);
		unset($this->updated_by);
		unset($this->updated_at);
  	}
}

// EOF