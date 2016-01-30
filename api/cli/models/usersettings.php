<?php

/**
 * UserSettings
 *
 * @package Models
 * @author Joshwa Fugett
 * @version 0.1
 * @access public
 */
class UserSettings extends CI_Model
{
	// constants for setting names
	const SORT_NAMES_BY 			= 'namesortorder';
	const DATE_FORMAT	 			= 'dateformat';
	const DATETIME_FORMAT 			= 'datetimeformat';
	const DATE_FORMAT_DISPLAY 		= 'dateformatdisplay';
	const DATETIME_FORMAT_DISPLAY   = 'datetimeformatdisplay';
	const DATE_FORMAT_SELECTION 	= 'dateformatselection';
	const TIME_FORMAT_SELECTION 	= 'timeformatselection';
	const CURRENCY_SYMBOL 			= 'currencysymbol';
	const CURRENCY_LEAD 			= 'currencylead';
	const DATE_FORMAT_SQL			= 'dateformatsql';
	const DATETIME_FORMAT_SQL		= 'datetimeformatsql';
	const DATETIME_FORMAT_SQL2		= 'datetimeformatsql2';

	const PRACTICE_NAME 			= 'practicename';
	const EMAIL_PRIMARY 			= 'emailprimary';
	const START_PAGE 				= 'startpage';
	const EMAIL_ALTERNATIVE 		= 'emailalternative';
	const UNITS_FORMAT 				= 'unitsformat';
	const UNITS_FORMAT_DISPLAY 		= 'unitsformatdisplay';
	const PHONE_FORMAT_DISPLAY 		= 'phoneformatdisplay';
	const SHARE_PATIENT_INFORMATION = 'sharepatientinformation';

	const TABS 						= 'customtab';
	const DASHBOARD_PANE			= 'staff3Pane';

	const REFERRAL_ITEMS_PER_PAGE	= 'referralitemsperpage';
	const PATIENT_ITEMS_PER_PAGE	= 'patientitemsperpage';
	const LAB_ITEMS_PER_PAGE		= 'orderitemsperpage';
	const TODO_ITEMS_PER_PAGE		= 'todoitemsperpage';
	const MSG_ITEMS_PER_PAGE		= 'msgitemsperpage';
	const DOC_ITEMS_PER_PAGE		= 'docitemsperpage';
	const USER_ITEMS_PER_PAGE 		= 40;
	const SHOW_PROCEDURE_MODIFIERS	= 'procedure_modifiers';

	// config settings for drug interactions
	const INTERACT_DRUG_DRUG_ENABLE = 'interactdrugdrugenable';
	const INTERACT_DRUG_ALLERGY_ENABLE = 'interactdrugallergyenable';
	const INTER_DRUG_SEVERE			= 'interdrugsevere';
	const INTER_DRUG_MODERATE		= 'interdrugmoderate';
	const INTER_DRUG_MILD			= 'interdrugmild';
	const INTER_ALLERGY_SEVERE		= 'interallergysevere';
	const INTER_ALLERGY_MODERATE	= 'interallergymoderate';
	const INTER_ALLERGY_MILD		= 'interallergymild';

	// emergency state
	const EMERGENCY_STATE			= 'emergencystate';

	const ORDER_LAST_NAME 			= 0;
	const ORDER_FIRST_NAME 			= 1;


	private $sDateFormat = 'm/d/Y';			// stores the DateFormat for the user
    private $sTimeFormat = 'g:i A';			// stores the TimeFormat for the user
    private $nPhoneFormat = 3;				// stores the PhoneFormat for the user
    private $nUnitsFormat = 1;				// stores the UnitsFormat for the user

	private $aSettings = array(				// stores other settings
		'namesortorder' => 1,
		'dateformat' => 'm/d/Y',
		'datetimeformat' => 'm/d/Y H:m:i',
		'dateformatsql' => 'Y-m-d',
		'datetimeformatsql' => 'Y-m-d H:m;i',
		'currencysymbol' => '$',
		'currencylead' => 1
	);

    /**
     * UserSettings::__construct()
     *
     * @return void
     */
    public function __construct()
    {
    	// call the parents constructor so the object is instantiated properly
        parent::__construct();
    }


	// retrieves a single user setting and caches it
    public function get($sName, $default = NULL)
    {
   		if (isset($this->aSettings[$sName]))
		   return ($this->aSettings[$sName]);
		else
			log_message('error', 'unset value encountered in userSettings::get - ' . $sName);
   		return ($default);
   	}


    /**
     * UserSettings::getDateFormat()
     *
     * @description - returns the DateFormat if it's already set, otherwise gets the DateFormat and then returns it
     * @return string $this->sDateFormat
     */
    public function getDateFormat()
    {
		return ($this->sDateFormat);
    }


	/**
	 * UserSettings::getTimeFormat()
	 *
	 * @description - returns the TimeFormat if it's already set, otherwise gets the TimeFormat and then returns it
	 * @return string $this->sTimeFormat
	 */
	public function getTimeFormat()
	{
		return ($this->sTimeFormat);
	}


	/**
	 * UserSettings::getPhoneFormat()
	 *
	 * @description - returns the PhoneFormat if it's already set, otherwise gets the PhoneFormat and then returns it
	 * @return int $this->nPhoneFormat
	 */
	public function getPhoneFormat()
	{
		return ($this->nPhoneFormat);
	}


	/**
	 * UserSettings::getUnitsFormat()
	 *
	 * @description - returns the UnitsFormat if it's already set, otherwise gets the UnitsFormat and then returns it
	 * @return string $this->sUnitFormat
	 */
	public function getUnitsFormat()
	{
		return ($this->nUnitsFormat);
	}

    public function set($sName, $sValue)
    {
   		$this->aSettings[$sName] = $sValue;
    }
}

// EOF