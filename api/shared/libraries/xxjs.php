<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * js
 *
 * @package js
 * @author Joshwa Fugett
 * @version 0.1
 * @access public
 *
 * @description - This file allows us to build up the entire js from a common library and keeps duplicates from creeping in from include files and
 *			  other similar code
 */
class js
{
	public $CI; // public instance of CI
	public $sJsPath = '';
	public $aJsFiles = array();
	public $sJsCode = '';
//	public $printMode = FALSE;

	/**
	 * js::__construct()
	 *
	 * @description - This function sets the instance of CI and gets the base js path from the config file
	 * @return void
	 */
	public function __construct()
	{
		// set the CI variable to the current instance of CI
		$this->CI =& EP_Controller::getInstance();

		// get the path to the js files from the config
		$sJsConfig = $this->CI->config->item('js_path');

		// set the reference to the js path
		$this->sJsPath = $sJsConfig;
	}

	/**
	 * js::addJs()
	 *
	 * @description - Adds a js file to the js array that will be output on a page
	 * @param string $sFilePath - the relative path from the js directory of the js file, no leading slash
	 * @return void
	 */
	public function addJs($sFilePath)
	{
		// check to see if the js file has already been added to the array
		if (!in_array($sFilePath, $this->aJsFiles))
		{
			// if the file doesn't already exist add it otherwise ignore it
			$this->aJsFiles[] = $sFilePath;
		}
	}

	public function addJsCode($sCode)
	{
		$this->sJsCode .= $sCode . "\r\n";
	}

	/**
	 * js::output()
	 *
	 * @description - outputs the html needed to link the js files to a page
	 * @return string $output - the html code for all js files that the page will use
	 */

	public function resetJs()
	{
		$this->aJsFiles = array();
	}

	public function output()
	{
		// variable for storing the output of js files
		$output = '';

		if($this->CI->input->is_ajax_request())
		{
			return $output;
		}

		$basePath = '';

		$i = 0;
		foreach ($this->aJsFiles as $sJs)
		{
			// build the full html link to the js file
			if ($sJs == 'jquery.min.js')
			{
				// DEV NOTE: please ensure that local jquery verison is the same as CDN
				if ($this->CI->getEnvironment() == 'development')
				{
					$output .= '<script type="text/javascript" src="' . $basePath . $this->sJsPath . 'jquery.min.js' . '"></script>' . "\r\n";
				} else {
					$output .= '<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>' . "\r\n";
				}
			} else if ($sJs == 'jquery-ui.min.js') {
				if ($this->CI->getEnvironment() == 'development')
				{
					$output .= '<script type="text/javascript" src="' . $basePath . $this->sJsPath . 'jquery-ui.min.js' . '"></script>' . "\r\n";
				} else {
					$output .= '<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/jquery-ui.min.js"></script>' . "\r\n";
				}
			} else {
				$output .= '<script type="text/javascript" src="' . $basePath . $this->sJsPath . $sJs . '"></script>' . "\r\n";
			}
		}

		$output .= '<script type="text/javascript">';
		if (strlen($this->sJsCode) != 0)
			$output .= $this->sJsCode;
		$output .= "</script>\r\n";

		// return the js so that the view can add it to the page
		return $output;
	}

	public function loadCrystal()
	{
		$crystalPath = 'crystalline';
		if($this->CI->getEnvironment() == 'production'){
//			$crystalPath .= '.min';
		}
		$this->addJs($crystalPath . '.js');

		if($this->CI->getEnvironment() == 'production'){
//			$this->addJsCode('CRYSTAL.useCompressed = true;');
   		}
	}
}

// EOF
