<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class EP_Zip extends CI_Zip {

	function _get_mod_time($dir)
	{
		$time = time();
		return $time;
	}
}