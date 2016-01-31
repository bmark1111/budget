<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * css
 *
 * @package css
 * @author Joshwa Fugett
 * @version 0.1
 * @access public
 *
 * @description - This file allows us to build up the entire css from a common library and keeps duplicates from creeping in from include files and
 *              other similar code
 */
class css
{
    public $CI; // public instance of CI
    public $sCssPath = '';
    public $aCssFiles = array();
    //public $printMode = FALSE;

    /**
     * css::__construct()
     *
     * @description - This function sets the instance of CI and gets the base css path from the config file
     * @return void
     */
    public function __construct()
    {
        // set the CI variable to the current instance of CI
        $this->CI =& EP_Controller::getInstance();

        // get the path to the css files from the config
        $sCssConfig = $this->CI->config->item('css_path');

        // set the reference to the css path
        $this->sCssPath = $sCssConfig;
    }

    /**
     * css::addCss()
     *
     * @description - Adds a css file to the css array that will be output on a page
     * @param string $sFilePath - the relative path from the css directory of the css file, no leading slash
     * @return void
     */
    public function addCss($sFilePath)
    {
        // check to see if the css file has already been added to the array
        if (!in_array($sFilePath, $this->aCssFiles))
        {
            // if the file doesn't already exist add it otherwise ignore it
            $this->aCssFiles[] = $sFilePath;
        }
    }

    public function resetCss()
    {
    	$this->aCssFiles = array();
    }

    /**
     * css::output()
     *
     * @description - outputs the html needed to link the css files to a page
     * @return string $output - the html code for all css files that the page will use
     */
    public function output()
    {
        // variable for storing the output of css files
        $output = '';

        if($this->CI->input->is_ajax_request())
        {
        	return $output;
        }

/*
		if($this->printMode)
		{
			$this->CI->load->helper('file');
			foreach($this->aCssFiles as $sCss)
			{
				//$output .= APPPATH . '../public' . $this->sJsPath . $sJs;
				$output .= '<style type="text/css">';
				$output .= read_file(APPPATH . '../public' . $this->sCssPath . $sCss);
				$output .= '</style>';
			}
			return $output;
		}
*/
		$basePath = '';

		$i = 0;
        foreach ($this->aCssFiles as $sCss)
        {
            // build the full html link to the css file
            $output .= '<link rel="stylesheet" type="text/css" href="' . $basePath . $this->sCssPath . $sCss . '" />' . "\r\n";
        }

        // return the css so that the view can show it to the user
        return $output;
    }
}

// EOF