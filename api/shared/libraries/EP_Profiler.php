<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * EP_Profiler
 *
 * @description - used to add Server and Session information to the profiler
 * @package Profiler
 * @author Joshwa Fugett
 * @version 0.1
 * @access public
 */
class EP_Profiler extends CI_Profiler
{
    public $CI; // public instance of CI

    /**
     * EP_Profiler::__construct()
     *
     * @return
     * @description - constructor for overriding CI_Profiler
     */
    public function __construct()
    {
        // call the parent constructor
        parent::__construct();

        // set the CI variable to the current instance of CI
        $this->CI =& EP_Controller::getInstance();

        // make sure that the language file is loaded for the profiler as it's required
        $this->CI->load->language('profiler');
    }

    /**
     * EP_Profiler::_compile_session()
     *
     * @description - used to get the session information into it's html form
     * @return
     */
    private function _compile_session()
    {
    	if(APPLICATION == 'ADMIN')
    	{
    		$_SESSION = $this->CI->session->userdata;
    	}
        $output  = "\n\n";
        $output .= '<fieldset style="border:1px solid #999000;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee">';
        $output .= "\n";
        $output .= '<legend style="color:#999000;">&nbsp;&nbsp;Session Data&nbsp;&nbsp;</legend>';
        $output .= "\n";

        if (count($_SESSION) == 0)
        {
            $output .= "<div style='color:#999000;font-weight:normal;padding:4px 0 4px 0'>No Session Data Found</div>";
        }
        else
        {
            $output .= "\n\n<table cellpadding='4' cellspacing='1' border='0' width='100%'>\n";

            foreach ($_SESSION as $key => $val)
            {
                if ( ! is_numeric($key))
                {
                    $key = "'".$key."'";
                }

                $output .= "<tr><td width='50%' style='color:#000;background-color:#ddd;'>&#36;_SESSION[".$key."]&nbsp;&nbsp; </td><td width='50%' style='color:#999000;font-weight:normal;background-color:#ddd;'>";
                if (is_array($val))
                {
                    $output .= "<pre>" . htmlspecialchars(stripslashes(print_r($val, true))) . "</pre>";
                }
                else
                {
                    $output .= htmlspecialchars(stripslashes($val));
                }
                $output .= "</td></tr>\n";
            }

            $output .= "</table>\n";
        }
        $output .= "</fieldset>";

        return $output;
    }

    /**
     * EP_Profiler::_compile_server()
     *
     * @description - used to get the server information into it's html output form
     * @return
     */
    private function _compile_server()
    {
        $output  = "\n\n";
        $output .= '<fieldset style="border:1px solid #F0000F;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee">';
        $output .= "\n";
        $output .= '<legend style="color:#F0000F;">&nbsp;&nbsp;Server Data&nbsp;&nbsp;</legend>';
        $output .= "\n";

        if (count($_SERVER) == 0)
        {
            $output .= "<div style='color:#F0000F;font-weight:normal;padding:4px 0 4px 0'>No Server Information Loaded</div>";
        }
        else
        {
            $output .= "\n\n<table cellpadding='4' cellspacing='1' border='0' width='100%'>\n";

            foreach ($_SERVER as $key => $val)
            {
                if ( ! is_numeric($key))
                {
                    $key = "'".$key."'";
                }

                $output .= "<tr><td width='50%' style='color:#000;background-color:#ddd;'>&#36;_SERVER[".$key."]&nbsp;&nbsp; </td><td width='50%' style='color:#F0000F;font-weight:normal;background-color:#ddd;'>";
                if (is_array($val))
                {
                    $output .= "<pre>" . htmlspecialchars(stripslashes(print_r($val, true))) . "</pre>";
                }
                else
                {
                    $output .= htmlspecialchars(stripslashes($val));
                }
                $output .= "</td></tr>\n";
            }

            $output .= "</table>\n";
        }
        $output .= "</fieldset>";

        return $output;
    }

    /**
     * EP_Profiler::run()
     *
     * @description - actually builds up all the seperate pieces for the profiler
     * @return
     */
    public function run()
    {
        $output = "<div id='codeigniter_profiler' style='clear:both;background-color:#fff;padding:10px;'>";

        $output .= $this->_compile_uri_string();
        $output .= $this->_compile_controller_info();
        $output .= $this->_compile_memory_usage();
        $output .= $this->_compile_benchmarks();
        $output .= $this->_compile_get();
        $output .= $this->_compile_post();
        $output .= $this->_compile_session();
        $output .= $this->_compile_server();
        $output .= $this->_compile_queries();

        $output .= '</div>';

        return $output;
    }

    /**
	 * Compile Queries
	 *
	 * @return	string
	 */
	protected function _compile_queries()
	{
		$dbs = array();
		if($this->CI != NULL)
		{
			$dbs = $this->CI->aDBs;
		}

		// Let's determine which databases are currently connected to
		/*foreach (get_object_vars($this->CI) as $CI_object)
		{
			if (is_object($CI_object) && is_subclass_of(get_class($CI_object), 'CI_DB') )
			{
				$dbs[] = $CI_object;
			}
		}*/

		//$dbs[] =& $this->CI->db;

		if (count($dbs) == 0)
		{
			$output  = "\n\n";
			$output .= '<fieldset id="ci_profiler_queries" style="border:1px solid #0000FF;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee">';
			$output .= "\n";
			$output .= '<legend style="color:#0000FF;">&nbsp;&nbsp;'.$this->CI->lang->line('profiler_queries').'&nbsp;&nbsp;</legend>';
			$output .= "\n";
			$output .= "\n\n<table style='border:none; width:100%'>\n";
			$output .="<tr><td style='width:100%;color:#0000FF;font-weight:normal;background-color:#eee;padding:5px'>no dbs".$this->CI->lang->line('profiler_no_db')."</td></tr>\n";
			$output .= "</table>\n";
			$output .= "</fieldset>";

			return $output;
		}

		// Load the text helper so we can highlight the SQL
		$this->CI->load->helper('text');

		// Key words we want bolded
		$highlight = array('SELECT', 'DISTINCT', 'FROM', 'WHERE', 'AND', 'LEFT&nbsp;JOIN', 'ORDER&nbsp;BY', 'GROUP&nbsp;BY', 'LIMIT', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'OR&nbsp;', 'HAVING', 'OFFSET', 'NOT&nbsp;IN', 'IN', 'LIKE', 'NOT&nbsp;LIKE', 'COUNT', 'MAX', 'MIN', 'ON', 'AS', 'AVG', 'SUM', '(', ')');

		$output  = "\n\n";

		foreach ($dbs as $db)
		{
			$output .= '<fieldset style="border:1px solid #0000FF;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee">';
			$output .= "\n";
			$output .= '<legend style="color:#0000FF;">&nbsp;&nbsp;'.$this->CI->lang->line('profiler_database').':&nbsp; '.$db->database.'&nbsp;&nbsp;&nbsp;'.$this->CI->lang->line('profiler_queries').': '.count($db->queries).'&nbsp;&nbsp;&nbsp;</legend>';
			$output .= "\n";
			$output .= "\n\n<table style='width:100%;'>\n";

			if (count($db->queries) == 0)
			{
				$output .= "<tr><td style='width:100%;color:#0000FF;font-weight:normal;background-color:#eee;padding:5px;'>".$this->CI->lang->line('profiler_no_queries')."</td></tr>\n";
			}
			else
			{
				foreach ($db->queries as $key => $val)
				{
					$time = number_format($db->query_times[$key], 4);

					$val = highlight_code($val, ENT_QUOTES);

					foreach ($highlight as $bold)
					{
						$val = str_replace($bold, '<strong>'.$bold.'</strong>', $val);
					}

					$output .= "<tr><td style='padding:5px; vertical-align: top;width:1%;color:#900;font-weight:normal;background-color:#ddd;'>".$time."&nbsp;&nbsp;</td><td style='padding:5px; color:#000;font-weight:normal;background-color:#ddd;'>".$val."</td></tr>\n";
				}
			}

			$output .= "</table>\n";
			$output .= "</fieldset>";

		}

		return $output;
	}


	// --------------------------------------------------------------------

}

// EOF