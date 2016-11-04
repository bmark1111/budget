<?php
/*
 * upload_map.php
 * Brian Markham 05/06/2015
 *
*/
class upload_map extends Nagilum {

	public $table = 'upload_map';

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = FALSE) {
		parent::__construct($id);
	}

}
//EOF