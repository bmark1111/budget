<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// ------------------------------------------------------------------------

/**
* validationError
*
* Checks for a validation error and outputs the required div if necessary
*
* @access	public
* @param	form, element
* @return	null
*/
if (!function_exists('validationError'))
{
	function validationError($form, $param)
	{
		if (!empty($form['validationErrors'][$param]))
		{
			echo '<div class="errorMessage">'.$form['validationErrors'][$param].'</div>';
		}
	}
}
