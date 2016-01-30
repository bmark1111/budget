<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * EP_Form_validation
 *
 * @package Form Validation
 * @author Joshwa Fugett
 * @version 0.4
 * @access public
 *
 * @description - extension of the built in Form Validation library. This currently only handles the ajax validation run but can easily have
 *                  new validation methods added to it by using the CI_Form_validation.php file as a template.
 */
class EP_Form_validation extends CI_Form_validation
{
	public $currentModel = NULL;
	public $form = NULL;

    /**
     * EP_Form_validation::__construct()
     *
     * @description - used to construct the extended version of the form validation library. Rules can be passed in here though we should avoid doing
     *              so for consistency
     * @param array $rules - please see CI documentation
     * @return void
     */
    public function __construct($rules = array())
    {
        parent::__construct($rules);
    }

    /**
     * EP_Form_validation::getErrorsArray()
     *
     * @description - this simply returns the private error array held by the parent class
     * @return array
     */
    public function getErrorsArray()
    {
        return $this->_error_array;
    }

    /**
     * EP_Form_validation::ajaxRun()
     *
     * @description - This method allows us to use the built in form validation library with our ajax handler. It uses the normal run routine and
     *              behaves identically. However it will automatically setup the field, error_message, and previous value for the fields within
     *              the ajax output.
     * @param string $group - used to pass through to parent please see CI documentation
     * @return boolean $succes - indicates whether there was an error with the validation or not
     */
    public function ajaxRun($form, $group = '')
    {
    	$this->form = $form;

        // run the base validation routine
        $success = $this->run($form, $group);

        // get any errors that exist
        $errors = $this->getErrorsArray();

        // iterate over the array and pass any errors into the ajax library for handling
        foreach($errors as $field => $error)
        {
            // create the AjaxValidation object that the ajax library requires
            $validationError = new AjaxValidation($field, $error, $this->set_value($field));
            $this->CI->ajax->addValidation($validationError);
        }

        // return the success or failure of the validation
        return $success;
    }

    public function getForm()
    {
    	return $this->form;
    }

    public function valid_phone($str)
    {
    	// here we strip out the extra phone characters
    	// this is a hair quicker than how we were doing it before as it has the Digits in a map rather than looping through characters
    	$str = preg_replace('/\D/', '', $str);

    	// we need to make sure that the phone number is 10 characters
    	if(strlen($str) != 10)
    	{
    		return FALSE;
    	}

    	// then we make sure that the number isn't the same character all the way through
    	$repeat_number = in_array($str, array(
			'0000000000',
			'1111111111',
			'2222222222',
			'3333333333',
			'4444444444',
			'5555555555',
			'6666666666',
			'7777777777',
			'8888888888',
			'9999999999'
		));

		if($repeat_number)
		{
			return FALSE;
		} else {
			return TRUE;
		}
    }

    public function valid_date($date) {
    	$stamp = strtotime($date);
    	if(!is_numeric($stamp))
    	{
    		// TODO: add in handling of partial dates here
    		return FALSE;
    	}

    	$startDate = new DateTime($date);
    	$date = $startDate->format('Y-m-d');
    	return TRUE;
	}

    public function run($form, $group = '')
    {
		$this->_error_array = array();
    	$this->form = $form;
    	return parent::run($group);
    }

    public function _execute($row, $rules, $postdata = NULL, $cycles = 0)
	{
		// If the $_POST data is an array we will run a recursive call
		if (is_array($postdata))
		{
			foreach ($postdata as $key => $val)
			{
				$this->_execute($row, $rules, $val, $cycles);
				$cycles++;
			}

			return;
		}

		// --------------------------------------------------------------------

		// If the field is blank, but NOT required, no further tests are necessary
		$callback = FALSE;
		if ( ! in_array('required', $rules) AND is_null($postdata))
		{
			// Before we bail out, does the rule contain a callback?
			if (preg_match("/(callback_\w+)/", implode(' ', $rules), $match))
			{
				$callback = TRUE;
				$rules = (array('1' => $match[1]));
			}
			else
			{
				return;
			}
		}

		// --------------------------------------------------------------------

		// Isset Test. Typically this rule will only apply to checkboxes.
		if (is_null($postdata) AND $callback == FALSE)
		{
			if (in_array('isset', $rules, TRUE) OR in_array('required', $rules))
			{
				// Set the message type
				$type = (in_array('required', $rules)) ? 'required' : 'isset';

				if ( ! isset($this->_error_messages[$type]))
				{
					if (FALSE === ($line = $this->CI->lang->line($type)))
					{
						$line = 'The field was not set';
					}
				}
				else
				{
					$line = $this->_error_messages[$type];
				}

				// Build the error message
				$message = sprintf($line, $this->_translate_fieldname($row['label']));

				// Save the error message
				$this->_field_data[$row['field']]['error'] = $message;

				if ( ! isset($this->_error_array[$row['field']]))
				{
					$this->_error_array[$row['field']] = $message;
				}
			}

			return;
		}

		// --------------------------------------------------------------------

		// Cycle through each rule and run it
		foreach ($rules As $rule)
		{
			$_in_array = FALSE;

			// We set the $postdata variable with the current data in our master array so that
			// each cycle of the loop is dealing with the processed data from the last cycle
			if ($row['is_array'] == TRUE AND is_array($this->_field_data[$row['field']]['postdata']))
			{
				// We shouldn't need this safety, but just in case there isn't an array index
				// associated with this cycle we'll bail out
				if ( ! isset($this->_field_data[$row['field']]['postdata'][$cycles]))
				{
					continue;
				}

				$postdata = $this->_field_data[$row['field']]['postdata'][$cycles];
				$_in_array = TRUE;
			}
			else
			{
				$postdata = $this->_field_data[$row['field']]['postdata'];
			}

			// --------------------------------------------------------------------

			// Is the rule a callback?
			$callback = FALSE;
			if (substr($rule, 0, 9) == 'callback_')
			{
				$rule = substr($rule, 9);
				$callback = TRUE;
			}

			// Strip the parameter (if exists) from the rule
			// Rules can contain a parameter: max_length[5]
			$param = FALSE;
			if (preg_match("/(.*?)\[(.*)\]/", $rule, $match))
			{
				$rule	= $match[1];
				$param	= $match[2];
			}

			// Call the function that corresponds to the rule
			if ($callback === TRUE)
			{
    			if (!(method_exists($this->CI, $rule) || method_exists($this->currentModel, $rule)))
				{
					continue;
				}

				if(method_exists($this->CI, $rule))
				{
					// Run the function and grab the result
					$result = $this->CI->$rule($postdata, $param);
				} else {
					$result = $this->currentModel->$rule($postdata, $param);
				}

				// Re-assign the result to the master data array
				if ($_in_array == TRUE)
				{
					$this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
				}
				else
				{
					$this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
				}

				// If the field isn't required and we just processed a callback we'll move on...
				if ( ! in_array('required', $rules, TRUE) AND $result !== FALSE)
				{
					continue;
				}
			}
			else
			{
				if ( ! method_exists($this, $rule))
				{
					// If our own wrapper function doesn't exist we see if a native PHP function does.
					// Users can use any native PHP function call that has one param.
					if (function_exists($rule))
					{
						$result = $rule($postdata);

						if ($_in_array == TRUE)
						{
							$this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
						}
						else
						{
							$this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
						}
					}

					continue;
				}

				$result = $this->$rule($postdata, $param);

				if ($_in_array == TRUE)
				{
					$this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
				}
				else
				{
					$this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
				}
			}

			// Did the rule test negatively?  If so, grab the error.
			if ($result === FALSE)
			{
				if ( ! isset($this->_error_messages[$rule]))
				{
					if (FALSE === ($line = $this->CI->lang->line($rule)))
					{
						$line = 'Unable to access an error message corresponding to your field name.';
					}
				}
				else
				{
					$line = $this->_error_messages[$rule];
				}

				// Is the parameter we are inserting into the error message the name
				// of another field?  If so we need to grab its "field label"
				if (isset($this->_field_data[$param]) AND isset($this->_field_data[$param]['label']))
				{
					$param = $this->_translate_fieldname($this->_field_data[$param]['label']);
				}

				// Build the error message
				$message = sprintf($line, $this->_translate_fieldname($row['label']), $param);

				// Save the error message
				$this->_field_data[$row['field']]['error'] = $message;

				if ( ! isset($this->_error_array[$row['field']]))
				{
					$this->_error_array[$row['field']] = $message;
				}

				return;
			}
		}
	}

	public function clear_rules()
	{
		$this->_field_data = array();
	}
}

// EOF
