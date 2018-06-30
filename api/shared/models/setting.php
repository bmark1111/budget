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

}
//EOF