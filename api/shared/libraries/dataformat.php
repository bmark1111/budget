<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * dataFormat
 *
 * @package DataFormat
 * @author Joshwa Fugett
 * @version 0.3
 * @access public
 *
 * @description - This class has basic data formatting methods that can be called from views or controllers
 */
class dataFormat
{
	public $CI; // instance of the current CI object
	private $settings = NULL;

    /**
     * dataFormat::__construct()
     *
     * @description - sets the CI instance
     * @return void
     */
    public function __construct()
    {
    	$this->CI =& EP_Controller::getInstance();
    }

    /**
     * dataFormat::date()
     *
     * @description - takes a mysql datetime object and formats it based on the users settings
     * @param string $sDate - mysql dateTime field
     * @return string $sFormattedDate - formatted date string
     */
    public function date($sDate)
    {
    	if(!isset($this->settings))
    	{
    		$this->settings = new userSettings();
		}

		if (strpos($sDate, '0000-00-00') !== FALSE || empty($sDate))
		{
			return NULL;
		}

    	// create a new DateTime object from the value passed in
    	$oDateTime = new DateTime($sDate);

    	// format the DateTime object based on the format in the users settings
		$sFormattedDate = $oDateTime->format($this->settings->getDateFormat());


    	// return the formatted string
    	return $sFormattedDate;
    }

    /**
     * dataFormat::dateTime()
     *
     * @description - takes a mysql datetime object and formats it based on the users settings
     * @param string $sDateTime - mysql dateTime field
     * @return string $sFormattedDate - formatted dateTime string
     */
    public function dateTime($sDateTime)
    {
    	if(!isset($this->settings))
    	{
    		$this->settings = new userSettings();
    	}
    	// create a new DateTime object from the value passed in
    	$oDateTime = new DateTime($sDateTime);

    	// format the DateTime object based on the format in the users settings
    	$sFormattedDate = $oDateTime->format($this->CI->UserSettings->getDateFormat() . ' ' . $this->CI->UserSettings->getTimeFormat());

    	// return the formatted string
    	return $sFormattedDate;
    }

	/**
     * dataFormat::dateTimeFromInt()
     *
     * @description - takes a unix timestamp and formats it based on the users settings
     * @param string $sDateTime - unix timestamp
     * @return string $sFormattedDate - formatted dateTime string
     */
    public function dateTimeFromInt($nTimeStamp)
    {
    	if(!isset($this->settings))
    	{
    		$this->settings = new userSettings();
    	}
    	// force recognition of the timestamp
    	//$nTimeStamp = '@' . $nTimeStamp;

    	// create a new DateTime object from the value passed in
    	$oDateTime = new DateTime();
    	$oDateTime->setTimestamp($nTimeStamp);

    	// format the DateTime object based on the format in the users settings
    	$sFormattedDate = $oDateTime->format($this->CI->UserSettings->getDateFormat() . ' ' . $this->CI->UserSettings->getTimeFormat());

    	// return the formatted string
    	return $sFormattedDate;
    }

    /**
     * dataFormat::dateFromInt()
     *
     * @description - takes a unix timestamp and formats it based on the users settings
     * @param string $sDateTime - unix timestamp
     * @return string $sFormattedDate - formatted date string
     */
    public function dateFromInt($nTimeStamp)
    {
    	if($nTimeStamp == NULL)
    	{
    		return NULL;
    	}
    	if(!isset($this->settings))
    	{
    		$this->settings = new userSettings();
    	}
    	// force recognition of the timestamp
    	//$nTimeStamp = '@' . $nTimeStamp;

    	// create a new DateTime object from the value passed in
    	$oDateTime = new DateTime();
    	$oDateTime->setTimestamp($nTimeStamp);

    	// format the DateTime object based on the format in the users settings
    	$sFormattedDate = $oDateTime->format($this->CI->UserSettings->getDateFormat());

    	// return the formatted string
    	return $sFormattedDate;
    }


	/*
	 * dataFormat::sqlToDisplay()
	 *
	 * @description - convert SQL formatted date or an integer and converts to a friendly display date
	 * @param mixed $sDate - the MySQL datetime value as a string, or an integer UNIX_TIMESTAMP value
	 *
	 * Note: If NULL or 0 is passed in, will return an empty string.
	 * Note: This is used in the Patient Portal when we need to display a friendly, readable date.
	 * No user configuration of date formats are available so we're using something that's hard coded.
	 */
	public function sqlToDisplay($sDate)
	{
		if ($sDate == NULL || intval($sDate) == 0)
			return ('');

		if (intval($sDate) == strval($sDate))
		{
			$dispDate = new DateTime();
			$dispDate->setTimestamp(intval($sDate));
		}
		else
			$dispDate = DateTime::createFromFormat('Y-m-d H:i:s', $sDate);

		return ($dispDate->format('M j, Y H:i:s'));
	}


    /**
     * dataFormat::time()
     *
     * @description - takes a mysql datetime object and formats it based on the users settings
     * @param string $sTime - mysql dateTime field
     * @return string $sFormattedDate - formatted time string
     */
    public function time($sTime)
    {
    	// removed && intval($sTime) == 0 due to the fact that mysql stores 24:00 as 00:00)
    	// if this is an issue we'll need to come up with a better solution than intval
    	if ($sTime == NULL)
			return ('');

    	if(!isset($this->settings))
    	{
    		$this->settings = new userSettings();
    	}
    	// create a new DateTime object from the value passed in
    	$oDateTime = new DateTime($sTime);

    	// format the DateTime object based on the format in the users settings
    	$sFormattedDate = $oDateTime->format($this->CI->UserSettings->getTimeFormat());

    	// return the formatted string
    	return $sFormattedDate;
    }

    /**
     * dataFormat::sqlDate()
     *
     * @description - takes a userf formatted date object and formats it to mysql Date format
     * @param string $sTime - mysql dateTime field
     * @return string $sFormattedDate - formatted time string
     */
    public function sqlDate($sDate)
    {
    	if(!empty($sDate))
    	{
    		$timeStamp = strtotime($sDate);
    		return date('Y-m-d', $timeStamp);
    	}
    }

    /**
     * dataFormat::sqlDateTime()
     *
     * @description - takes a userf formatted date object and formats it to mysql Datetime format
     * @param string $sTime - mysql dateTime field
     * @return string $sFormattedDate - formatted time string
     */
    public function sqlDateTime($sDate)
    {
    	if(!empty($sDate))
    	{
    		$timeStamp = strtotime($sDate);
    		return date('Y-m-d H:i:s', $timeStamp);
    	}
    }

    /**
     * dataFormat::integer()
     *
     * @description - takes a numeric value and formats it as an int
     * @param numeric $val - a numeric value that will be formatted as an integer
     * @return string $ret - formatted int
     */
    public function integer($val)
    {
    	// format the number as an integer
    	$ret = number_format($val, 0);

    	// return the formatted value
		return $ret;
    }

    /**
     * dataFormat::number()
     *
     * @description - takes a numeric value and formats it as a float
     * @param numeric $val - a numeric value that will be formatted as a float
     * @param integer $nDecimal - number of decimal places to format the number to
     * @return string $ret - formatted float
     */
    public function number($val, $nDecimal = 2)
	{
		// format the number with the given number of decimal places
		$ret = number_format($val, $nDecimal);

		// return the formatted value
		return $ret;
	}

    /**
     * dataFormat::nullToEmptyString()
     *
	 * @description - returns nulls as empty strings for use with displaying to users
	 * @param mixed $val - a value that will be returned as an empty string if its null
     * @return mixed $ret - formatted value
     */
    public function nullToEmptyString($val)
	{
		// check for a value of null
		if ($val === NULL)
		{
			$ret = '';
		} else {
			$ret = $val;
		}

		return $ret;
    }

    /**
     * dataFormat::currency()
     *
     * @description - takes a numeric value and formats it as currency
     * @param numeric $val - a numeric value that will be formatted as currency
     * @return string $ret - formatted currency
     */
    public function currency($val)
    {
    	// add the dollar sign to the return value
    	$ret = '$';

    	// format the number accordingly
    	$ret .= number_format($val, 2);

		// return the formatted value
		return $ret;
    }

        /**
     * dataFormat::units()
     *
     * @description - takes two units and formats them according to the users settings
     * @param string $sUnitOne - a string representation of the numerator unit
     * @param string $sUnitTwo - a string representation of the denominator unit
     * @return string $ret - formatted units string
     */
    public function units($sUnitOne, $sUnitTwo)
    {
    	if(!isset($this->settings))
    	{
    		$this->settings = new userSettings();
    	}
    	$nUnitsFormat = $this->CI->UserSettings->getUnitsFormat();

    	switch ($nUnitsFormat)
    	{
    		default:
    		case 1:
    			$sSeperator = '/';
    			break;
   			case 2:
   				$sSeperator = ':';
   				break;
			case 3:
				$sSeperator = ' per ';
				break;
    	}

    	$ret = $sUnitOne . $sSeperator . $sUnitTwo;

		// return the formatted string
		return $ret;
    }

    /**
     * dataFormat::phone()
     *
     * @description - formats the phone based on the users settings
     * @param string $sPhone
     * @return $sRet - formatted string
     */
    public function phone($sPhone)
    {
    	if(!isset($this->settings))
    	{
    		$this->settings = new userSettings();
    	}
    	// builds the regex
    	$regex = '/^(\d{3})(\d{3})(\d{4})$/';

		// determine which format to use based on the users settings
		switch ($this->CI->UserSettings->getPhoneFormat())
		{
			default:
			case 1:		$sFormat = '($1) $2-$3 x $4';
				break;
			case 2:		$sFormat = '$1.$2.$3 x $4';
				break;
			case 3:		$sFormat = '$1-$2-$3 x $4';
				break;
		}

		// format the string properly
		$sRet = preg_replace($regex, $sFormat, $sPhone);

		// if the end of the string is an empty extension remove it
		if (substr(trim($sRet), -2) == ' x')
		{
			$sRet = substr($sRet, 0, strlen(trim($sRet)) - 2);
		}

		// return the formatted phone number
		return $sRet;
    }

    /**
     * dataFormat::textToHtml()
     *
     * @description - formats a text string so that it is safe for use within HTML
     * @param string $sData - text to be formatted
     * @return string $sData - formatted version of the text string
     */
    public function textToHtml($sData)
    {
    	// use html special chars on the data
    	$sData = htmlspecialchars($sData, ENT_QUOTES);

		// change return newline to newline only
		$sData = str_replace("\r\n", "\n", $sData);

		// remove any remaining return characters
		$sData = str_replace("\r", '', $sData);

		// replace any double newlines with an empty paragraph
		$sData = str_replace("\n\n", '<p/>', $sData);

		// replace any remaining newlines with a break
		$sData = str_replace("\n", '<br/>', $sData);

		// return the formatted data
		return $sData;
    }

	public function isValidDate($date = NULL)
	{
		if ($date == '0000-00-00 00:00:00' || $date == '0000-00-00' || $date == NULL)
			return '';

    	$stamp = strtotime($date);
    	if(!is_numeric($stamp))
		{
			$dti = new DateTimeInput();
			$isPartial = $dti->isPartialDate($date);
			if (!$isPartial)
			{
				return FALSE;
			}
			unset($dti);
			unset($isPartial);
    	}
		$userSettings = new UserSettings();
		$format = $userSettings->getDateFormat();

		$dateTimeInput = new DateTimeInput();
		$isPartial = $dateTimeInput->isPartialDate($date);

		if ($isPartial)
		{
			// if its a partial date and we have the month, then we need to get rid of the day:
			if (!empty($dateTimeInput->month))
			{
				$formatPartial = str_replace(array('d', 'D', 'j', 'l', 'w', 'z'), '', $format);  // remove all the references to day in user format setting
				$date = $dateTimeInput->format($formatPartial);

				// get rid of any delimiters orphaned at the begining or end of the date string:
				$replacements = array('/(^\D)?/i','/(\D$)?/i');
				$date = preg_replace($replacements, '', $date);

				// find any delimiter that appears twice with no numbers between them:
				$doubles = '/(\D{2})/i'; // matches any non-digit appearing twice
				preg_match($doubles, $date, $matches);
				if (count($matches))
				{
					$delimiter = substr($matches[0], 0, 1); // get only one of the delimiters which appear twice
					$date = preg_replace($doubles, $delimiter, $date); // replace the repeated delimiter with just one
				}
			} else {
				$date = $dateTimeInput->year;
			}

		} else {
			$date = $dateTimeInput->format($format);
		}
    	return $date;
	}
}

include_once(dirname(__FILE__).'/datetimeinput.php');
// EOF
