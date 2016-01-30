<?php
class EP_Input extends CI_Input
{

	var $_enable_xss			= FALSE; // Set automatically based on config setting

	public function __construct()
	{
		log_message('debug', "EP_Input Class Initialized");
		$this->_allow_get_array	= (config_item('allow_get_array') === TRUE);

		$this->_enable_csrf		= (config_item('csrf_protection') === TRUE);

		global $SEC;
		$this->security =& $SEC;

		// Do we need the UTF-8 class?
		if (UTF8_ENABLED === TRUE)
		{
			global $UNI;
			$this->uni =& $UNI;
		}

		// Sanitize global arrays
		$this->_sanitize_globals();
	}

	/**
	* Fetch an item from the GET array
	*
	* @access	public
	* @param	string
	* @param	bool
	* @return	string
	*/
	function get($index = NULL, $xss_clean = TRUE)
	{
		// Check if a field has been provided
		if ($index === NULL AND ! empty($_GET))
		{
			$get = array();

			// loop through the full _GET array
			foreach (array_keys($_GET) as $key)
			{
				$get[$key] = $this->_fetch_from_array($_GET, $key, $xss_clean);
			}
			return $get;
		}

		return $this->_fetch_from_array($_GET, $index, $xss_clean);
	}

	/**
	* Fetch an item from the POST array
	*
	* @access	public
	* @param	string
	* @param	bool
	* @return	string
	*/
	function post($index = NULL, $xss_clean = TRUE)
	{
		// Check if a field has been provided
		if ($index === NULL AND ! empty($_POST))
		{
			$post = array();

			// Loop through the full _POST array and return it
			foreach (array_keys($_POST) as $key)
			{
				$post[$key] = $this->_fetch_from_array($_POST, $key, $xss_clean);
			}
			return $post;
		}
		
		return $this->_fetch_from_array($_POST, $index, $xss_clean);
	}
}
//EOF
