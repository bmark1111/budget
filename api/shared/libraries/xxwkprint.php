<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WebkitToPDF Printing Class
 * For generating PDF's
 *
 * !!!!! Until Live Server can accept requests to itself, all external resources must be loaded inline. Images should be pulled from an external server.
 * !!!!!!!! You can now strip any js and css assets and pass them to the library as a string. See below example "Striping and Injections". Note that you can inject and strip from 'header', 'content', 'footer'  :: Have Fun!
 * Use like so;
 *
 * 			$html = $this->index();
 *			$page = $this->_output($html, TRUE);
 *
 *			$this->_output() has a second variable (Bool) that if true, loads all css and js inline.
 *
 *
 *
 * Basic Usage is
  		$page = $this->load->view('page', $data, TRUE);
  		$this->load->library('wkprint');
  		$sPDF = $this->wkprint->generate($page);
 		force_download('file.pdf', $sPDF);


 * Advanced Usage is
 		$header = $this->load->view('header', $data, TRUE);
 		$content = $this->load->view('content', $data, TRUE);
 		$footer = '<script type="text/javascript">$("body").css("background-color", "green");</script>';
 		$footer .= $this->load->view('footer', $data, TRUE);

 		$this->load->library('wkprint');
 		$this->wkprint->headerString = $header;
 		$this->wkprint->contentString = $content;
 		$this->wkprint->footerString = $footer;
 		$this->wkprint->marginTop = 10;
 		$this->wkprint->debugJS = TRUE;
 		$sPDF = $this->wkprint->generate();
 		force_download('file.pdf', $sPDF);


 * Strpping & Injections Usage is
 		$sHTML = $this->load->view('thingy', NULL, TRUE);

 		//load the doc manager & PDF library
		$this->load->library(array('docmgr', 'wkprint'));
		$this->load->helper(array('file', 'download'));

		//load and merge the additional css
		$css = '';
		$css .= read_file(APPPATH . '../public/css/financials/statements.css');
		$css .= read_file(APPPATH . '../public/css/financials/statements_print.css');

		//write the HTML & PDF
		$this->wkprint->contentString = $sHTML;
		$this->wkprint->stripContentCSS = TRUE;
		$this->wkprint->stripContentJS = TRUE;
		$this->wkprint->contentCSS = $css;
		$this->wkprint->contentJS = "Alert('I am injected JS!')";
		$this->wkprint->debugJS = TRUE;
		$sPDF = $this->wkprint->generate();
		force_download('file.pdf', $sPDF);
 *
 **/

class Wkprint {

	//Pirvate Class variables
	private $_ci;				// CodeIgniter instance
	private $_executablePath = ''; //path to wkhtmltopdf, default is SHAREPATH/libraries/wkhtmltopdf/wkhtmltopdf
	private $_errorEmail = 'devlogs@proovebio.com';
	private $_tmpPath = '/tmp/';
	public $filesUsed = array();

	//If you want to keep the tmp files for debug, set this to false when you instantiante the library
	public $cleanMess = TRUE; //will automatically remove tmp files if set to true

	//Options
	public $orientation = "Portrait"; //Set orientation to Landscape or Portrait
	public $paperSize = "Letter"; //A4 is wkprints default, this overrides it.
	public $disableSmartShrinking = FALSE; //Set to true to disable
	public $setDpi = FALSE;
	public $returnMode = "string"; //pdf contents in string, path to file
	public $fileName = '';
	public $headerPath = "";
	public $headerString = "";
	public $footerPath = "";
	public $footerString = "";
	public $contentString = "";
	public $contentPath = "";
	public $debugJS = FALSE;
	public $ignoreJSErrors = FALSE;
	public $marginTop = NULL;
	public $marginBottom = NULL;
	public $marginLeft = NULL;
	public $marginRight = NULL;
	public $pagination = FALSE;
	public $pageBreak = FALSE;
	public $inlineAssets = FALSE;
	public $footerPageNumbers = FALSE;

	//Strip Content
	public $stripContentCSS = FALSE;
	public $stripContentJS = FALSE;
	public $stripHeaderCSS = FALSE;
	public $stripHeaderJS = FALSE;
	public $stripFooterCSS = FALSE;
	public $stripFooterJS = FALSE;

	//Injectable Content
	public $contentCSS = NULL;
	public $contentJS = NULL;
	public $headerCSS = NULL;
	public $headerJS = NULL;
	public $footerCSS = NULL;
	public $footerJS = NULL;

	function __construct()
	{
        $this->_ci =& EP_Controller::getInstance();
		log_message('debug', 'WKPrint Class Initialized');

		//load helpers
		$this->_ci->load->helper('file');
		$this->_ci->load->helper('string');

		//ensure executable exits
		if(!$this->_canExecute())
		{
			$this->_error("cannot execute");
		}
	}

	/*
	* Generate is the public method for executing the file
	* Accepts html and returns pdf string
	* To use the advanced mode, sBasic must not be set, and the minium option would be contentString or contentPath
	*/
	public function generate($sBasic = NULL)
	{
		if(!empty($sBasic))
		{	//generate process for basic usage
			//get & set tmp names
			$htmlName = 'wkHTML_' . $this->_randomName() . '.html';
			$pdfName = 'wkPDF_' . $this->_randomName() . '.pdf';

			//write html
			$htmlFile = $this->_tempFile($htmlName, $sBasic);
			$pdfFile = $this->_tempFile($pdfName, $sBasic);

			//execute the command
			$pdf = $this->_execute($this->_executablePath . ' ' . $this->_tmpPath . $htmlName . ' ' . $this->_tmpPath . $pdfName);
			return $pdf;
//			//if the PDF was succesfully generated, output!
//			if($pdf)
//			{
//				$output = $this->_outputPDF($pdfName);
//			}
//
//			if(!empty($output))
//			{
//				$this->_cleanup();
//			}
//
//			return $output;
		} else {
			//for advanced mode
			//get & set tmp names
			$pdfName = 'wkPDF_' . $this->_randomName() . '.pdf';

			//write pdf
			$pdfFile = $this->_tempFile($pdfName, $sBasic);

			//inject/strip the content
			$this->_finalizeContent();

			//Build the command line options
			$options = $this->_buildOptions();

			//execute the command
			$pdf = $this->_execute($this->_executablePath . ' ' . $options . ' ' . $this->_tmpPath . $pdfName);
			return $pdf;
//			//if the PDF was succesfully generated, output!
//			if($pdf)
//			{
//				$output = $this->_outputPDF($pdfName);
//			}
//
//			if(!empty($output))
//			{
//				$this->_cleanup();
//			}
//
//			return $output;
		}
	}

	private function _finalizeContent()
	{
		if($this->inlineAssets)
		{
			if(!empty($this->headerString))
			{
				$this->_inlineAssets($sMode = 'header');
			}
			if(!empty($this->footerString))
			{
				$this->_inlineAssets($sMode = 'footer');
			}
			$this->_inlineAssets();
		}

		//strip stuff
		$this->_stripContentCSS();
		$this->_stripContentJS();
		$this->_stripHeaderCSS();
		$this->_stripHeaderJS();
		$this->_stripFooterCSS();
		$this->_stripFooterJS();

		//Injections
		$this->_injectContentCSS();
		$this->_injectContentJS();
		$this->_injectHeaderCSS();
		$this->_injectHeaderJS();
		$this->_injectFooterCSS();
		$this->_injectFooterJS();
		$this->_stripNonBreakingSpace();
	}

	private function _inlineAssets($sMode = 'content')
	{
		if($sMode == 'content')
		{
			$html = $this->contentString;
			$mode = 'content';
		}
		elseif($sMode == 'header')
		{
			$html = $this->headerString;
			$mode = 'header';
		}
		elseif($sMode == 'footer')
		{
			$html = $this->footerString;
			$mode = 'footer';
		}

		$cssMode = $mode.'CSS';
		$jsMode = $mode.'JS';

		//load the dom
		$dom = new DOMDocument();
		//this overrides any erros from unrecognized html5 tags
		libxml_use_internal_errors(true);
		$dom->loadHTML($html);
		libxml_clear_errors();

		//get the css elements
		$link = $dom->getElementsByTagName('link');
		foreach($link as $css)
		{
			$assets['css'][] = $css->getAttribute('href');
		}

		//get the js elements
		$script = $dom->getElementsByTagName('script');
		foreach($script as $js)
		{
			$assets['js'][] = $js->getAttribute('src');
		}

		//read the assets and spill them inline
		$inlineCss = '';
		if(isset($assets['css']))
		{
			foreach($assets['css'] as $css)
			{
				//remove any png links
				if(strpos($css, '.png') === FALSE)
				{
					$cssCode = read_file(APPPATH . '../public/'.$css);
					$inlineCss .= $cssCode;
				}
			}
		}
		$inlineJs = '';
		if(isset($assets['js']))
		{
			foreach($assets['js'] as $js)
			{
				//exlude wym editor from the js
				if(strpos($js, 'wym') === FALSE)
				{
					$jsCode = read_file(APPPATH . '../public/'.$js);
					$inlineJs .= $jsCode;
				}
			}
		}

		//if any predefined css or js exists, save it! the css and js
		$tempCSS = '';
		if(!empty($this->$cssMode))
		{
			$tempCSS = $this->$cssMode;
		}
		$tempJS = '';
		if(!empty($this->$jsMode))
		{
			$tempJS = $this->$jsMode;
		}

		//set the inlined js and css
		$this->$cssMode = $inlineCss;
		$this->$jsMode = $inlineJs;

		//add the temp assets if they exist
		$this->$cssMode .= $tempCSS;
		$this->$jsMode .= $tempJS;
		$this->inlineAssets = FALSE;
		$this->_finalizeContent();
	}

	private function _buildOptions()
	{
		//setup null return
		$return = "";

		//setup null options
		$options = array();
		$options[] = $this->_marginTop();
		$options[] = $this->_marginBottom();
		$options[] = $this->_marginLeft();
		$options[] = $this->_marginRight();
		$options[] = $this->_orientation();
		$options[] = $this->_paperSize();
		$options[] = $this->_disableSmartShrinking();
		$options[] = $this->_setDpi();
		$options[] = $this->_headerPath();
		$options[] = $this->_headerString();
		$options[] = $this->_footerPath();
		$options[] = $this->_footerString();
		$options[] = $this->_contentString();
		$options[] = $this->_contentPath();
		$options[] = $this->_debugJS();
		$options[] = $this->_ignoreJSErrors();
		$options[] = $this->_footerPageNumbers();

		$this->_debug(print_r($options, TRUE));

		foreach($options as $option)
		{
			if(!empty($option))
			{
				$return .= " " . $option;
			}
		}

		$this->_debug("built options :: " . $return);

		return $return;
	}

	private function _headerPath()
	{
		$return = "";
		if(!empty($this->headerPath))
		{
			$fileContent = read_file($this->headerPath);
			if(!$fileContent)
			{
				$this->_error('cannot read custom `headerPath` file content');
			}
			else
			{
				$fileName =  'wkHTML_customHeader_' . $this->_randomName() . '.html';
				$file = $this->_tempFile($fileName, $fileContent);
				$return = "--header-html " . $this->_tmpPath . $file;
			}
		}

		return $return;
	}

	private function _headerString()
	{
		$return = "";
		if(!empty($this->headerString))
		{
			$fileName =  'wkHTML_customHeader_' . $this->_randomName() . '.html';
			$file = $this->_tempFile($fileName, $this->headerString);
			$return = "--header-html " . $this->_tmpPath . $file;
		}

		return $return;
	}

	private function _footerPath()
	{
		$return = "";
		if(!empty($this->footerPath))
		{
			$fileContent = read_file($this->footerPath);
			if(!$fileContent)
			{
				$this->_error('cannot read custom `footerPath` file content');
			}
			else
			{
				$filename =  'wkHTML_customFooter_' . $this->_randomName() . '.html';
				$file = $this->_tempFile($filename, $fileContent);
				$return = "--footer-html " . $this->_tmpPath . $file;
			}
		}

		return $return;
	}

	private function _footerString()
	{
		$return = "";
		if(!empty($this->footerString))
		{
			$fileName =  'wkHTML_customFooter_' . $this->_randomName() . '.html';
			$file = $this->_tempFile($fileName, $this->footerString);
			$return = "--footer-html " . $this->_tmpPath . $file;
		}

		return $return;
	}

	private function _contentString()
	{
		$return = "";
		if(!empty($this->contentString))
		{
			$filename =  'wkHTML_customContent_' . $this->_randomName() . '.html';
			$file = $this->_tempFile($filename, $this->contentString);
			$return = " " . $this->_tmpPath . $file;
		}

		return $return;
	}

	private function _contentPath()
	{
		$return = "";
		if(!empty($this->contentPath))
		{
			$fileContent = read_file($this->contentPath);
			if(!$fileContent)
			{
				$this->_error('cannot read custom `contentPath` file content');
			}
			else
			{
				$filename =  'wkHTML_customContent_' . $this->_randomName() . '.html';
				$file = $this->_tempFile($filename, $fileContent);
				$return = " " . $this->_tmpPath . $file;
			}
		}

		return $return;
	}

	private function _orientation()
	{
		return " --orientation " . $this->orientation;
	}

	private function _paperSize()
	{
		return " --page-size " . $this->paperSize;
	}

	private function _footerPageNumbers()
	{
		$return = "";
		if ($this->footerPageNumbers)
		{
			$return = " --footer-center [page]/[topage]";
		}
		return $return;
	}

	private function _marginTop()
	{
		$return = "";
		if($this->marginTop !== NULL)
		{
			$return = " --margin-top " . intval($this->marginTop);
		}
		return $return;
	}

	private function _marginBottom()
	{
		$return = "";
		if($this->marginBottom  !== NULL)
		{
			$return = " --margin-bottom " . intval($this->marginBottom);
		}
		return $return;
	}

	private function _marginLeft()
	{
		$return = "";
		if($this->marginLeft  !== NULL)
		{
			$return = " --margin-left " . intval($this->marginLeft);
		}
		return $return;
	}

	private function _marginRight()
	{
		$return = "";
		if($this->marginRight  !== NULL)
		{
			$return = " --margin-right " . intval($this->marginRight);
		}
		return $return;
	}

	private function _disableSmartShrinking()
	{
		$return = "";
		if($this->disableSmartShrinking)
		{
			$return = " --disable-smart-shrinking ";
		}
		return $return;
	}

	private function _setDpi()
	{
		$return = "";
		if($this->setDpi)
		{
			$return = " --dpi " . intval($this->setDpi);
		}
		return $return;
	}

	private function _debugJS()
	{
		$return = "";
		if($this->debugJS)
		{
			$return = " --debug-javascript ";
		}

		return $return;
	}

	private function _ignoreJSErrors()
	{
		$return = "";
		if($this->ignoreJSErrors)
		{
			$return = " --load-error-handling ignore";
		}
	}

	private function _injectContentCSS()
	{
		if(!empty($this->contentCSS))
		{
			//inject the CSS into the document
			$this->_inject('css', 'contentCSS', "contentString");
		}
	}

	private function _injectHeaderCSS()
	{
		if(!empty($this->headerCSS))
		{
			$this->_inject('css', 'headerCSS', 'headerString');
		}
	}

	private function _injectFooterCSS()
	{
		if(!empty($this->footerCSS))
		{
			$this->_inject('css', 'footerCSS', 'footerString');
		}
	}

	private function _injectContentJS()
	{
		if(!empty($this->contentJS))
		{
			$this->_inject('js', 'contentJS', 'contentString');
		}
	}

	private function _injectHeaderJS()
	{
		if(!empty($this->headerJS))
		{
			$this->_inject('js', 'headerJS', 'headerString');
		}
	}

	private function _injectFooterJS()
	{
		if(!empty($this->additionalCSS))
		{
			$this->_inject('js', 'footerJS', 'footerString');
		}
	}

	private function _inject($sType = NULL, $sContent = NULL, $sSection = NULL)
	{
		if(empty($sType) || empty($sContent) || empty($sSection))
		{
			return false;
		}

		//Build up the content accordingly
		switch($sType)
		{
			case "css":
				//Build up the css with tags
				$sStyleStart = '<style type="text/css">';
				$sStyleEnd = '</style>';
				$sInjectedContent = $sStyleStart . $this->$sContent . $sStyleEnd;
				$this->$sSection = str_ireplace('</head>', $sInjectedContent . '</head>', $this->$sSection);
				break;
			case "js":
				//Build up the js with tags
				$sScriptStart = '<script type="text/javascript">';
				$sScriptEnd = '</script>';
				$sInjectedContent = $sScriptStart . $this->$sContent . $sScriptEnd;
				$this->$sSection = str_ireplace('</head>', $sInjectedContent . '</head>', $this->$sSection);
				break;
		}
	}

	private function _stripContentCSS()
	{
		if(!$this->stripContentCSS)
		{
			return FALSE;
		}

		$start = stripos($this->contentString, '<style');
		$end = strripos($this->contentString, '</style>');

		if($start !== FALSE && $end !== FALSE)
		{
			$this->contentString = substr($this->contentString, 0, $start) . substr($this->contentString, $end + 7);
		}
	}

	private function _stripContentJS()
	{
		if(!$this->stripContentJS)
		{
			return FALSE;
		}

		$start = stripos($this->contentString, '<script');
		$end = strripos($this->contentString, '</script>');

		if($start !== FALSE && $end !== FALSE)
		{
			$this->contentString = substr($this->contentString, 0, $start) . substr($this->contentString, $end + 7);
		}
	}

	private function _stripHeaderCSS()
	{
		if(!$this->stripHeaderCSS)
		{
			return FALSE;
		}

		$start = stripos($this->headerString, '<style');
		$end = strripos($this->headerString, '</style>');

		if($start !== FALSE && $end !== FALSE)
		{
			$this->headerString = substr($this->headerString, 0, $start) . substr($this->headerString, $end + 7);
		}
	}

	private function _stripHeaderJS()
	{
		if(!$this->stripHeaderJS)
		{
			return FALSE;
		}

		$start = stripos($this->headerString, '<script');
		$end = strripos($this->headerString, '</script>');

		if($start !== FALSE && $end !== FALSE)
		{
			$this->headerString = substr($this->headerString, 0, $start) . substr($this->headerString, $end + 7);
		}
	}

	private function _stripFooterCSS()
	{
		if(!$this->stripFooterCSS)
		{
			return FALSE;
		}

		$start = stripos($this->footerString, '<style');
		$end = strripos($this->footerString, '</style>');

		if($start !== FALSE && $end !== FALSE)
		{
			$this->footerString = substr($this->footerString, 0, $start) . substr($this->footerString, $end + 7);
		}
	}

	private function _stripFooterJS()
	{
		if(!$this->stripFooterJS)
		{
			return FALSE;
		}

		$start = stripos($this->footerString, '<script');
		$end = strripos($this->footerString, '</script>');

		if($start !== FALSE && $end !== FALSE)
		{
			$this->footerString = substr($this->footerString, 0, $start) . substr($this->footerString, $end + 7);
		}
	}

	private function _stripNonBreakingSpace()
	{
		if (empty($this->contentString))
		{
			return FALSE;
		}

		$start = 0;
		$end = strlen($this->contentString);

		if($start !== FALSE && $end !== FALSE)
		{
			$this->contentString = str_replace('&#160;', ' ', $this->contentString);
		}
	}

	private function _execute($sCommand = NULL)
	{
		//set the default to error
		$return = FALSE;
		$status = "error";

		//excetute the command and return the last line
		$exec = system($sCommand, $status);

		//check to see if system returned Done
		if($status != "Done")
		{
			$this->_error("failed to generate PDF");
		}

		//set the success
		$return = TRUE;

		//log the command and last line
		$this->_debug($sCommand . " :: process returned :: " . print_r($status, TRUE));

		return $return;
	}

	private function _outputPDF($pdfFileName)
	{
		log_message("debug", __METHOD__ . ' :: ' . print_r($pdfFileName, TRUE));
		//if file can be read
		if(!empty($pdfFileName))
		{
			//check the output method
			switch($this->returnMode)
			{
				case "string":
					$fileContent = read_file($this->_tmpPath . $pdfFileName);
					return $fileContent;
					break;
				case "path":
					return $this->_tmpPath . $pdfFileName;
					break;
			}
		}
	}

//	private function _cleanup()
	public function cleanup()
	{
		if(!empty($this->filesUsed) && $this->cleanMess)
		{
			foreach($this->filesUsed as $filename)
			{
				if(file_exists($this->_tmpPath . $filename))
				{
					unlink($this->_tmpPath . $filename);
				}
			}
		}
	}

	private function _fileContents($sFilename = NULL)
	{
		return read_file($this->_tmpPath . $sFilename);
	}

	private function _tempFile($sFilename = NULL, $sContent = NULL, $bReturnString = FALSE)
	{
		//write the file
		if(!write_file($this->_tmpPath . $sFilename, $sContent))
		{
			$this->_error("could not write html tmp file");
		}

		//log the file
		$this->_debug("wrote file :: " . print_r($sFilename, TRUE));

		//add the file to the array of files to be removed
		$this->filesUsed[] = $sFilename;

		//check if return file as string is true
		if($bReturnString)
		{
			return $this->_fileContents($sFilename);
		}

		//return the filename
		return $sFilename;
	}

	private function _randomName()
	{
		return random_string("alpha", 16);
	}

	//checks to see if wkhtml to pdf exists and that it can be used
	private function _canExecute()
	{
		$return = FALSE;

		//check for userdefined wkhtmltopdf executable
		if(!empty($this->_executablePath))
		{
			return TRUE;
		}

		//create a string of the server architecture
		$serverVersion = php_uname();

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		{
			//check for windows
			$tempdir = explode('/', $_SERVER['DOCUMENT_ROOT']);
			$this->_tmpPath = $tempdir[0] . $this->_tmpPath;
			$this->_executablePath = '"' . SHAREPATH . 'libraries/wkhtmltopdf2/wkhtmltopdf.exe"';
			$this->_debug("setting default executable. :: " . $this->_executablePath);
		} else {
			//check for 32Bit :: i386 / i586 / i686
			if(substr($serverVersion, -2) == "86")
			{
				//set the 32 bit binary
				$this->_executablePath = SHAREPATH . 'libraries/wkhtmltopdf2/wkhtmltopdf';
				$this->_debug("setting default executable. :: " . $this->_executablePath);
			}

			//check for 64
			if(substr($serverVersion, -2) == "64")
			{
				//check for OS X
				if(PHP_OS == "Darwin")
				{
					$this->_executablePath = SHAREPATH . 'libraries/wkhtmltopdf2/ls -al'
							. '-OS_X';
					$this->_debug("setting the  -OS_X suffix to executable. :: " . $this->_executablePath);
				}
				else
				{
					//set the default 64 bit
					$this->_executablePath = SHAREPATH . 'libraries/wkhtmltopdf2/wkhtmltopdf-amd64';
					$this->_debug("setting default executable. :: " . $this->_executablePath);
				}
			}
		}

		//ensure the path is set
		$fileInfoTypes = array('name', 'server_path', 'size', 'date', 'readable', 'writable', 'executable', 'fileperms');
		$fileInfo = get_file_info($this->_executablePath, $fileInfoTypes);

		//ensure executablePath is set
		if(!empty($this->_executablePath))
		{
			//check for file, if it exists && that it is executable
			if($fileInfo['executable'])
			{
				$return = TRUE;
			}
			else
			{
				//figure out if the file exists
				if(!$fileInfo)
				{
					$this->_error("wkhtmltopdf could not be found . " . print_r($fileInfo, TRUE));
				}

				//if it exists but it not executable
				if(!$fileInfo['executable'])
				{
					$this->_error("wkhtmltopdf is not executable." . print_r($fileInfo, TRUE));
				}
			}
		}

		return $return;
	}

	private function _debug($sMessage)
	{
		log_message("debug", $sMessage);
	}

	private function _error($sMessage = NULL, $sType = "CRITICAL")
	{
		if($sType == "CRITICAL")
		{
			$this->_debug("received critical error - " . $sMessage);
		} else {
			$this->_debug("received error - " . $sMessage);
		}

		//throw new Exception("We encountered an error. Please contact support.");
	}

}

/* End of file wkprint.php */
/* Location: ./shared/libraries/wkprint.php */
