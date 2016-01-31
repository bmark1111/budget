<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('array_to_csv'))
{
    function array_to_csv($array, $sep = TRUE)
    {
		$seperator = ($sep) ? '"': '';
		$lines = array();
		foreach ($array as $line)
		{
//			$lines[] = $seperator . implode('", "', $line) . $seperator . "\r\n";
			$lines[] = $seperator . implode("$seperator,$seperator", $line) . $seperator . "\r\n";
		}

		return implode('', $lines);
	}
}

//EOF