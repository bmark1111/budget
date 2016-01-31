<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// ------------------------------------------------------------------------

/**
* usage
*
* Checks for memory usage
*
* @access	public
* @param	comment
* @return	null
*/
if (!function_exists('usage'))
{
	function usage($comment)
	{
		log_message('debug', $comment . ' ' . round(memory_get_usage()/1024/1024,2) . 'MB');
	}
}

/**
* execution
*
* Checks for execution time
*
* @access	public
* @param	comment
* @return	null
*/
if (!function_exists('execution'))
{
	function execution($comment, $lastTime=false)
	{
		$time = microtime();
		$mtime = explode(" ",$time);
		$mtime = $mtime[1] + $mtime[0];
		if ($lastTime)
		{
			log_message('debug', $comment . ' ' . ($mtime - $lastTime) . ' seconds');
		} else {
			log_message('debug', $comment);
		}
		return $mtime;
	}
}
