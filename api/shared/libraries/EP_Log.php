<?php
class EP_Log extends CI_Log
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	public function write_log($level = 'error', $msg, $php_error = FALSE)
	{
		if ($this->_enabled === FALSE)
		{
			return FALSE;
		}

		$level = strtoupper($level);

		if ( ! isset($this->_levels[$level]) || ($this->_levels[$level] > $this->_threshold))
		{
			return FALSE;
		}

		$message = $level.' '.(($level == 'INFO') ? ' -' : '-').' '.$msg."\n";
//		error_log($message);

		$filepath = $this->_log_path.'log-'.date('Y-m-d').EXT;
		$message  = '';

		if ( ! file_exists($filepath))
		{
			$message .= "<"."?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?".">\n\n";
		}

		if ( ! $fp = @fopen($filepath, FOPEN_WRITE_CREATE))
		{
			return FALSE;
		}

		$message .= $level.' '.(($level == 'INFO') ? ' -' : '-').' '.date($this->_date_fmt). ' --> '.$msg."\n";
		flock($fp, LOCK_EX);
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);

		@chmod($filepath, FILE_WRITE_MODE);
		return TRUE;
	}
}
?>
