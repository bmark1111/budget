<?php

/**
 * Ajax
 *
 * @package Ajax
 * @author Joshwa Fugett
 * @version 0.21
 * @access public
 *
 * @description This class is used to handling the output of ajax calls
 *      This class can accept data as an array only, all other formats need to be passed in either as an array of ajaxObjects (See Below) or as a
 *      single error object depending on the method used to add the information
 *      To output the ajax from a controller you would simply use $this->ajax->output();
 */
class Ajax
{
    public $CI = NULL; // holds a reference to the CI object
    private $aErrorList = array(); // this is an array that will hold all of the AjaxError objects
    private $aNoticeList = array(); // this is an array that will hold all of the AjaxNotice objects
    private $aValidationList = array(); // this is an array that will hold all of the AjaxValidation objects
    private $aJsonList = array(); // this is a temporary array that is only used during output to build the entire output
    private $aDataList = array(); // this is the actual data array that most of the logic will use
    public $iSuccess = 1; // 1 indicates the request was successful 0 indicates some kind of failure.
                         // Additional information should be passed in through an AjaxError

    /**
     * Ajax::__construct()
     *
     * @return - No Return Value
     */
    public function __construct()
    {
        // set the CI instance to the currently instantiated instance of CI so that it can be used from extended controllers
        $this->CI =& EP_Controller::getInstance();
    }

	public function clearValidationErrors()
	{
		$this->aValidationList = array();	// empty the validation errors
		$this->aJsonList['success'] = 1;	// force success
	}

    /**
     * Ajax::output()
     *
     * @description - outputs the ajax array to the browser, can also be passed in additional data to output in the data array
     * @param optional array $json - This is additional data that should be added to the data array (will overwrite existing keys)
     * @return - void
     */
    public function output($aJson = NULL, $bReturn = FALSE)
    {
		// set the success to the requests success
        if (!isset($this->aJsonList['success']))
        {
            $this->aJsonList['success'] = $this->iSuccess;
        }

		// add any extra values to the output
 		if ($bReturn && is_array($bReturn))
		{
			foreach ($bReturn as $key => $value)
			{
				$this->aJsonList[$key] = $value;
			}
		}

        if($this->CI->form_validation->getForm() !== NULL)
        {
        	$this->aJsonList['form'] = $this->CI->form_validation->getForm();
        }

        // if there are any errors to add add them to the output
        if (count($this->aErrorList) > 0)
        {
            $this->aJsonList['errors'] = $this->aErrorList;
        }

        // if there are any notices to add add them to the output
        if (count($this->aNoticeList) > 0)
        {
            $this->aJsonList['notices'] = $this->aNoticeList;
        }

        // if there are any validation errors to add add them to the output
        if (count($this->aValidationList) > 0)
        {
            $this->aJsonList['validation'] = $this->aValidationList;
        }

      // add the json object to the dataList and overwrite any existing keys
        if (!empty($aJson))
        {
            $this->aDataList = array_merge($this->aDataList, $aJson);
        }

        // if the data list is not empty add it to the output
        if (count($this->aDataList) > 0 && count($this->aErrorList) == 0)
        {
            $this->aJsonList['data'] = $this->aDataList;
        }

		// timestamp the response
		$this->aJsonList['timestamp'] = mktime();

        // if the environment is not production add the profiler information to the ajax call
        if (ENVIRONMENT != 'production' && $this->CI->config->item('enable_profiler'))
        {
            // load the profiler class since we can't get to it from within CI
            $profiler = load_class('Profiler');

            // set the profiler variable by running the profiler
            if ($this->CI->config->item('enable_profiler'))
            {
            	$this->aJsonList['profiler'] = $profiler->run();
            }
            // make sure that the profiler is turned off since running it seems to turn it on
            $this->CI->output->enable_profiler(FALSE);
        }

        // encode the output list for json handling
        $json = $this->utf8_encode_all($this->aJsonList);
        $aOutput = json_encode($json, JSON_FORCE_OBJECT);

		// clear output buffer and write our data
		while (ob_get_level())				// clear any current output buffering
			ob_end_clean();

        // send the output to the browser
        header("cache-control: no-cache");
        header('Content-type: application/json; charset=utf-8');

		echo $aOutput;
		exit(0);
//		die($aOutput);
    }

    /**
     * Ajax::directOutput()
     *
     * @description - Sends the output directly out to javascript for use with autocomplete and similar needs
     * @param mixed $data - this is the data to send back out to javascript
     * @return void
     */

     public function directOutput($data)
     {
     	$data = json_encode($data);

     	header("cache-control: no-cache");
     	header('Content-type: application/json; charset=utf-8');

     	die($data);
     }

	 public function set_header($msg, $status)
	 {
		 header("HTTP/1.1 " . $status . " " . $msg);
//		 $this->setData(array('Error' => $msg));//
	 }

    /**
     * Ajax::utf8_encode_all()
     *
     * @description - takes it's input and converts it to the same format in utf8 encoded format
     * @param mixed $data - this is the data to utf8 encoded
     * @return mixed $data - the utf8 encoded format of the data passed into the object
     */
	protected function utf8_encode_all($data)
	{
		if (is_string($data))
		{
			return utf8_encode($data);
		}

		if( is_object($data) && is_a($data, 'Nagilum'))
		{
			$temp = $data->toArray();
			$data = $temp;
		}

		if (!is_array($data))
		{
			return $data;
		}

		$return = array();

		foreach($data as $key => $value)
		{
			$return[$key] = $this->utf8_encode_all($value);
		}

		return $return;
	}

    /**
     * Ajax::ignoreUserAbort()
     *
     * @description - sets the script to ignore user aborts
     * @return void
     */
    public function ignoreUserAbort()
    {
    	// set the script to ignore user aborts such as closing down the browser or going to another page
    	ignore_user_abort(TRUE);
    }

    /**
     * Ajax::errors()
     *
     * @description - allows you to add an array of AjaxError objects to the output
     * @param array AjaxError $aErrors - an array of AjaxError objects
     * @return void
     */
    public function errors($aErrors)
    {
        // if the input isn't an array throw an exception so that we can tell that we weren't using the correct method
        if (!is_array($aErrors))
        {
            throw new Exception('Ajax->validations() requires an array');
        }

        // pass each AjaxError object in the array through the error function so that we can ensure that it is the right type being passed in
        foreach ($aErrors as $oError)
        {
            $this->addError($oError);
        }
    }

    /**
     * Ajax::addError()
     *
     * @description - adds an AjaxError object to the errorList for output. Strong type checking is used so we can ensure that needed portions are passed in.
     * @param AjaxError $oError - the AjaxError be added to the output
     * @return void
     */
    public function addError(AjaxError $oError)
    {
        // since there was an error we need to set success to FALSE
        $this->iSuccess = 0;

        // add the error to the errorList for output
        $this->aErrorList[] = $oError;
    }

    /**
     * Ajax::notices()
     *
     * @description - allows you to add an array of AjaxNotice objects to the output
     * @param array AjaxNotice $aNotices - an array of AjaxNotice objects that need to be added to the noticeList
     * @return void
     */
    public function notices($aNotices)
    {
        // if the input isn't an array throw an exception so that we can tell that we weren't using the correct method
        if (!is_array($aNotices))
        {
            throw new Exception('Ajax->notices() requires an array');
        }

        // pass each AjaxNotice object in the array through the error function so that we can ensure that it is the right type being passed in
        foreach ($aNotices as $oNotice)
        {
            $this->addNotice($oNotice);
        }
    }

    /**
     * Ajax::addNotice()
     *
     * @description - adds an AjaxNotice object to the noticeList for output. Strong type checking is used so we can ensure that needed portions are passed in.
     * @param AjaxNotice $oNotice - the AjaxNotice to be added to the output
     * @return void
     */
    public function addNotice(ajaxNotice $oNotice)
    {
        // add the notice to the noticeList for output
        $this->aNoticeList[] = $oNotice;
    }

    /**
     * Ajax::validations()
     *
     * @description - allows you to add an array of AjaxValidation objects to the output
     * @param mixed $aValidations - an array of AjaxValidation objects to be added to the output
     * @return void
     */
    public function validations($aValidations)
    {
        // if the input isn't an array throw an exception so that we can tell that we weren't using the correct method
        if (!is_array($aValidations))
        {
            throw new Exception('Ajax->validations() requires an array');
        }

        // pass each AjaxValidation object in the array through the error function so that we can ensure that it is the right type being passed in
        foreach ($aValidations as $oValidation)
        {
            $this->addValidation($oValidation);
        }
    }

    /**
     * Ajax::addValidation()
     *
     * @description - adds an AjaxValidation object to the validationList for output. Strong type checking is used so we can ensure that needed portions are passed in.
     * @param mixed $oValidation - the AjaxValidation object to be added to the output
     * @return void
     */
    public function addValidation(ajaxValidation $oValidation)
    {
        // add the validation error to the validationList for output
        $this->aValidationList[] = $oValidation;
        $this->iSuccess = 0;
    }

    /**
     * Ajax::setData()
     *
     * @description - allows you to set data to the data array prior to output. Will overwrite previously set data
     * @param string $sKey - nullable key that will let you set the key that the data is added under
     * @param array $aData - the data to be added to the dataList
     * @return void
     */
    public function setData($sKey = NULL, $aData)
    {
        if ($sKey && !is_string($sKey))
        {
            throw new Exception('ajax data requires a string for a name so that it may be identified');
        }

        if ($sKey)
        {
            $this->aDataList[$sKey] = $aData;
        } else {
            $this->aDataList[] = $aData;
        }
    }

	/**
     * Ajax::resetData()
     *
     * @description - allows you to reset the data of the ajax object
     * @return void
     */
    public function resetData()
    {
        $this->aDataList = array();
    }

	/**
     * Ajax::redirect()
     *
     * @description - allows you to tell epmd to perform a window.href redirect in javascript
     * @return hats
     */    
    public function redirect($url = 'error')
    {
//    	$this->output(array('redirect' => $url));
    	$this->setData('redirect',$url);
    }
}

/**
 * AjaxError
 *
 * @package Ajax
 * @author Joshwa Fugett
 * @version 0.2
 * @access public
 *
 * @description - Used to add error messages to ajax output.
 */
class AjaxError
{
    public $error = NULL; // required error message
    public $file = NULL; // optional file name
    public $line = NULL; // option line number

    /**
     * AjaxError::__construct()
     *
     * @description - used to ensure validation on error message output through ajax
     * @param string $sError - the error message to display to the user
     * @param optional string $sFile - the file that the error occurred in
     * @param optional string $sLine - the line that the error occurred on
     * @return void
     */
    public function __construct($sError, $sFile = NULL, $sLine = NULL)
    {
        $this->error = $sError;
        $this->file = $sFile;
        $this->line = $sLine;
    }
}

/**
 * AjaxNotice
 *
 * @package Ajax
 * @author Joshwa Fugett
 * @version 0.2
 * @access public
 *
 * @description - Used to add notices to the ajax output
 */
class AjaxNotice
{
    public $message = NULL; // required message

    /**
     * AjaxNotice::__construct()
     *
     * @description - ensures that a message is included with each notice message
     * @param string $sMessage - required
     * @return
     */
    public function __construct($sMessage)
    {
        $this->message = $sMessage;
    }
}

/**
 * AjaxValidation
 *
 * @package Ajax
 * @author Joshwa Fugett
 * @version 0.2
 * @access public
 *
 * @description - Used to add validation messages to the ajax output
 */
class AjaxValidation
{
    public $fieldName = NULL; // required name of field the validation error occurred on
    public $errorMessage = NULL; // required validation error message
    public $previousValue = NULL; // optional previous value for the field

    /**
     * AjaxValidation::__construct()
     *
     * @description - used to ensure that both a field name and an error message are included in the validation message
     * @param string $sFieldName - required
     * @param string $sErrorMessage - required
     * @param mixed $previousValue - optional
     * @return
     */
    public function __construct($sFieldName, $sErrorMessage, $previousValue = NULL)
    {
        $this->fieldName = $sFieldName;
        $this->errorMessage = $sErrorMessage;
        $this->previousValue = $previousValue;
    }
}

// EOF
