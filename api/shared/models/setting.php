<?php
/*
 * setting.php
 * Brian Markham 04/03/2015
 *
*/
class setting extends Nagilum {

	public $table = 'setting';

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