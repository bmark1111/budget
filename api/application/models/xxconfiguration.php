<?php
/*
 * configuration.php
 * Brian Markham 04/03/2015
 *
*/
class configuration extends Nagilum
{
	public $table = 'configuration';

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct()
	{
		parent::__construct();
	}

}
//EOF