
<?php

/**
 * EP_Exceptions
 *
 * @package Shared
 * @author Dave Jesch
 * @version 0.9
 * @access public
 *
 * @description This class handles capturing and logging of errors and exception.
alter table error_log add backtrace_array text default null after backtrace;
select id, date_format(from_unixtime(created_at), '%m/%d/%y') as occured from error_log;
select id, msg, date_format(from_unixtime(created_at), '%m/%d/%y') as occured, backtrace_array
	from error_log order by id desc limit 1;
 */

class EP_Exceptions extends CI_Exceptions
{
	private $aErrInfo = NULL;

	// log exception info
	public function logException($Exception)
	{
		// called from shared/system/ep_exception_handler
		$message = $Exception->getMessage();
		$file = $Exception->getFile();
		$line = $Exception->getLine();
		$backtrace = $Exception->getTrace();

		$this->storeError('Exception', $message, $file, $line, $backtrace);
	}


	// override the CI handler to log PHP runtime error info
	function log_exception($severity, $message, $filepath, $line)
	{
		$severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

		$this->storeError($severity, $message, $filepath, $line, debug_backtrace(), 2);
	}


	// displays the error page to the user
	function show_error($heading, $message, $template = 'error_general', $status_code = 200)
	{
		if ($template == 'error_404')
		{
			include(APPPATH . 'errors/error_404.php');
			exit;
		}
		if($template == 'error_db')
		{
			$errorMessage = APPLICATION . ' ' . $heading . ': ' . $message[0] . "\n\r" . $message[1];
			if (APPLICATION == 'REST')
			{
				$errorMessage .= "\n\r" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			}
			$file = (!empty($message[3])) ? $message[3]: FALSE;
			$line = (!empty($message[4])) ? $message[4]: FALSE;
			$this->storeError('Database', $errorMessage, $file, $line);
			if (APPLICATION != 'REST' && APPLICATION != 'CLI')
			{
				return TRUE;
			} else {
				return $errorMessage . "\n\r" . $file . "\n\r" . $line . "\n\r";
			}
		}
		if ($this->aErrInfo == NULL)
		{
			if (APPLICATION == 'CLI')
			{
//				print_r(debug_backtrace());
			}
//			die('Error Info NULL');
			// when called from CI core, we need to call storeError() to get $aErrInfo populated
			$aErrData = error_get_last();
			$sMsg = implode(' ', (! is_array($message)) ? array($message) : $message);
			$this->storeError(intval($aErrData['type']), $sMsg, $aErrData['file'], $aErrData['line'], debug_backtrace(), 1);
		}

		$message = APPLICATION . ' ' . implode('\n\r', ( ! is_array($message)) ? array($message) : $message);
		if (APPLICATION == 'REST')
		{
			$message .= "\n\r" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}
if (ENVIRONMENT == 'production')
{
	include(APPPATH . 'errors/error.html');
	exit;
} else {
	die($message);
}
		while (ob_get_level())				// clear any current output buffering
		{
			ob_end_clean();
		}

		if (class_exists('EP_Controller'))
			$ctrl = EP_Controller::getInstance();
		else
			$ctrl = get_instance();

		// check if running in command line mode
		if ($ctrl->input->is_cli_request())
		{
			echo 'An error was encountered: ' . APPLICATION . ' ' . $this->aErrInfo['message'] . "\r\n\r\n";
			die();
		}

		// restart output buffering
		ob_start();

		// check for ajax requests and output ajax friendly content
		if (is_subclass_of($ctrl, 'EP_Controller') && $ctrl->input->is_ajax_request())
		{
			// turn off profiling
			$ctrl->config->set_item('enable_profiler', FALSE);
			$ctrl->ajax->resetData();
//			if (ENVIRONMENT == 'production')
//				$ctrl->ajax->addError(new AjaxError('An error was encountered'));
//			else
				$ctrl->ajax->addError(new AjaxError("Error: " . $message));
			$ctrl->ajax->output();
		}
		set_status_header($status_code);

		if (ENVIRONMENT == 'production')
		{
			include(APPPATH . 'errors/error.html');
		} else {
			die($message);
			// not sure if globalizing aErrorInfo is needed - maybe load the view?
			global $aErrorInfo;
			$aErrorInfo = $this->aErrInfo;
			include(APPPATH . 'errors/error_display' . EXT);
		}
		$buffer = ob_get_contents();
		ob_end_clean();
		die($buffer);
	}

	// stores error/exception information into our database
	private function storeError($sSeverity, $sMessage, $sFile, $nLine, $aBacktrace = NULL)
	{
		$sMessage = print_r($sMessage, TRUE) . ' in file ' . $sFile . ':' . $nLine . ' Severity: ' . $sSeverity;

		// Log the error to the log file
		log_message('error', '***Error occured: ' . $sMessage);

		// get the controller instance so we can get session data
		if (class_exists('EP_Controller'))
		{
			$ctrl = EP_Controller::getInstance();
		} else {
			$ctrl = get_instance();
		}

		if($aBacktrace)
		{
			$backtrace = print_r($aBacktrace, TRUE);
			$backtrace = substr($backtrace, 0, 65000);

			if($ctrl)
			{
				$log_threshold = $ctrl->config->item('log_threshold');
				if($log_threshold > 1)
				{
					log_message('error', "Backtrace: " . $backtrace);
				}
			}
		}

		// Log the error to the database
		$data = array();
		$data['message'] = $sMessage;
		if(isset($backtrace))
		{
			$data['backtrace'] = $backtrace;
		}
		if (is_a($ctrl, 'EP_Controller'))
		{
			$data['account_id'] = $ctrl->nAccount;
			$data['user_id'] = $ctrl->nUserId;
			$data['user_login'] = $ctrl->sUserName;
		}
		$data['environment'] = ENVIRONMENT;
		if(isset($_SERVER['HTTP_HOST']))
		{
			$data['http_host'] = $_SERVER['HTTP_HOST'];
		}
//		$data['uri'] = $ctrl->uri->uri_string();
		if(isset($_SERVER['HTTP_USER_AGENT']))
		{
			$data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		}
		if(isset($_SERVER['REMOTE_ADDR']))
		{
			$data['remote_address'] = $_SERVER['REMOTE_ADDR'];
		}
		if(isset($_GET))
		{
			$data['get_array'] = serialize($_GET);
		}
		if(isset($_POST))
		{
			$data['post_array'] = serialize($_POST);
		}
		if(isset($_SESSION))
		{
			$data['session_array'] = serialize($_SESSION);
			$data['session_key'] = session_id();
		}
		$date = new DateTime();
		$data['created_at'] = $date->format('Y-m-d H:i:s');

		if($ctrl)
		{
			//$ctrl->db->insert('ep_master.ci_error_log', $data);
			//$data['insert_id'] = $ctrl->db->insert_id();
		}

		//
		if(APPLICATION == 'REST' || APPLICATION == 'CLI')
		{
			$subject = ENVIRONMENT . ' Error Notification';
			$from = 'automated@proovebio.com';
			$to = 'devlogs@proovebio.com';

			$date = new DateTime();

			$message = '';
			$message .= 'Error: ' . $sMessage."\n";
			$message .= 'Error Date: ' . $date->format('Y-m-d H:i:s') . "\n";
//			$message .= 'Domain: ' . base_url() . "\n";//$this->aErrInfo['uri'] . "\n";
			$message .= 'Environment: ' . ENVIRONMENT . "\n\n";
			$message .= 'https://admin.proovebio.com/ci_errors/view/';// . $this->aErrInfo['insert_id'];

			if($ctrl)
			{
				$ctrl->load->library('email');

				$ctrl->email->from($from, 'Proove Bio Error');
				$ctrl->email->to($to);

				$ctrl->email->subject($subject);
				$ctrl->email->message($message);
				$ctrl->email->send();
			}
		}

		$this->aErrInfo = $data;
	}

	/**
	 * Native PHP error handler
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	function show_php_error($severity, $message, $filepath, $line)
	{
		$this->storeError($severity, $message, $filepath, $line);
	}
}

// EOF
