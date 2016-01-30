<?php
/**
 * @module app_data.php
 * @method appdata
 * Brian Markham 11/14/2015
 *
*/
class app_data extends Nagilum {

	protected $table = 'app_data';
	protected $primaryKey = FALSE;

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = NULL) {
		parent::__construct($id);
	}

}

// EOF