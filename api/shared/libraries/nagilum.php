<?php

/**
 * Nagilum
 *
 * @package - Nagilum
 * @author - Joshwa Fugett
 * @version 0.87
 * @access public
 */
class Nagilum extends ArrayObject implements IteratorAggregate
{
    protected $CI = NULL; // holds a reference to the CI object so that the model can access loaded libraries
    	// this is set on construction and won't change
    protected $db = NULL; // holds a reference to the db object attached to CI so that we can use $this->db instead of $this->CI->db
    	// this is set on construction and won't change
    private $inflector = NULL; // holds a refernce to the inflector helper just so we can be sure it's loaded
    	// this is set on construction and won't change
    protected $form_validation = NULL; // holds a reference to the form validation library used to validate the models
    	// this is set on constructtion and won't change
   	protected $dataFormat = NULL; // holda a reference to the data format library
	protected $input = NULL;

   	protected $table = NULL; // holds the current table name
   		// this is set at instantiation and won't change
    protected $model = NULL; // The singular name for this model used for references on joins
    	// this is set at instantiation and will only change in the case of relationships when they'll be stored as a relationship that doesn't match
   		// the default model name
    protected $parent = NULL; // Semi-private field used to track the parent model/id if there is one. For use with saving/deleting relationships
    	// this is only set during relationships
    protected $primaryKey = 'id'; // the primary key that is used in the constructor to automatically create the default record with that id ORM style
    	// this is set only at instantiation and will not change
    protected $tableFields = array(); // holds the fields for this table
    	// this is set at instantiation and won't change I want to build a cache for these so that they can be grabbed from there if they exist
    protected $tableMetaData = array(); // holds the metadata for the fields of this table
    	// this is set at instantiation and won't change I want to build a cache for these so that they can be grabbed from there if they exist to
   		// cut down on the number of queries when instatiating a lot of the same object
    protected $cType = 'data'; // this can be either a container or a data set based upon whether the query was a single or a multiple result call
							// if the object is a container you can't set or unset data directly on it as it's only a collection of objects
							// this field will also prevent you from calling save, and delete methods on the object
							// I have to remember to check on the type in all instances where I need to manipulate the children

	protected $stored = array(); // Used to keep track of the original values from the database, to prevent unecessarily changing fields
							// this is used to determine what needs to be saved
							// this should be set whenever a query is run
							// changeable only from within the class
    protected $data = array(); // holds the data for the results
    						// this can change at any point in time
    protected $rels = array(); // holds the relationship objects for this object
    						// this can change at any point in time
    protected $dataChanged = FALSE; // holds whether the object has changed or not, this will be set whenever a data element is set on the object
    						// changeable only from within the class
    protected $savable = TRUE; // this is used on direct queries to keep the object from saving so things don't get screwed up by saving something
    						// that shouldn't be savable
    						// changeable only from within the class
	protected $formId = 'Nagilum';	// form post id
	protected $preSaveHooks = array();  // used to store callback methods set from outside the class
							// can be changed at any time
    protected $postSaveHooks = array(); // used to store callback methods set from outside the class
    						// can be changed at any time
	protected $postInsertHooks = array(); // used to store callback methods set from outside the class
    						// can be changed at any time
	protected $postUpdateHooks = array(); // used to store callback methods set from outside the class
    						// can be changed at any time
    protected $preResultHooks = array(); // used to store callback methods set from outside the class
    						// can be changed at any time
    protected $postResultHooks = array(); // used to store callback methods set from outside the class
    						// can be changed at any time
	protected $preDeleteHooks = array(); // used to store callback methods set from outside the class
							// can be changed at any time

    protected $initPreSaveHooks = array();  // used to store callback methods set from outside the class
    						// setup at instantiation and can't be changed
    protected $initPostSaveHooks = array(); // used to store callback methods set from outside the class
    						// setup at instantiation and can't be changed
    protected $initPreResultHooks = array(); // used to store callback methods set from outside the class
    						// setup at instantiation and can't be changed
    protected $initPostResultHooks = array(); // used to store callback methods set from outside the class
    						// setup at instantiation and can't be changed
	protected $initPreDeleteHooks = array();

    protected $initHasMany = array(); // holds the original has many relationships for use with clear
    						// setup at instantiation and can't be changed
    protected $initHasOne = array(); // holds the original has one relationships for use with clear
    						// setup at instantiation and can't be changed

    public $hasMany = array(); // holds the has many relationships
    						// this can be set at any point but should only be set at runtime unless there's a specific reason to do otherwise
    public $hasOne = array(); // holds the has one relationships
    						// this can be set at any point but should only be set at runtime unless there's a specific reason to do otherwise

    protected $errors = array(); // Contains any errors that occur during validation, saving, or other database access.
    						// this is set only at runtime from within the class
    protected $valid = FALSE; // The result of validate is tored here
    						// this is set only at runtime from within the class
    protected $initValidationRules = array(); // used by clear to restore the validation rules to their default state
    						// this is set only at instantiation
    public $validationRules = array(); // array holds the validation rules that inserts, updates, and saves will be check against.
				// See the form_validation class for details. I need to figure out how to handle validation of child classes
	protected $initSkipValidation = TRUE; // used by clear to restore the skip validation to its default value
							// set at instantiation
    public $skipValidation = TRUE;  // This tells whether to skip validation or not on inserts, updates and saves
    						// may be set at any time
	protected $validated = FALSE; // tracks whether or not the object has already been validated
							// set only from within the class

    public $softDeleteField = 'is_deleted'; // this is the name of the soft delete field that is called automatically if it exists
    						// set only at instantiation
    public $createdAtField = 'created_at'; // this is the name of the created at field which is set automatically if it exists
    						// set only at instantiation
    public $createdByField = 'created_by'; // this is the name of the created by field which is set automatically if it exists
    						// set only at instantiation
    public $updatedAtField = 'updated_at'; // this is the name of the udpated at field which is set automatically if it exists
    						// set only at instantiation
    public $updatedByField = 'updated_by'; // this is the name of the updated by field which is set automatically if it exists
    						// set only at instantiation
    protected $useOldStyleAutoFields = FALSE; // this determines whether to use ints or DateTime objects for created at and udpate at fields
    						// set only at instantiation
    protected $resultFilters = array(); // this is for removing rows from a result set.
							// set only at instantiation

    public $autoTransaction = FALSE; // if TRUE automatically wraps every save and delete in a transaction
    						// this may be set at any time
	protected $initAutoTransaction = FALSE; // this is set only at instantiation

    public $autoPopulateHasMany = TRUE; // if TRUE will automatically populate has many fields
    						// this may be set at any time but will only have an effect when queries are run
	protected $initAutoPopulateHasMany = TRUE; // only set at instantiation
    public $autoPopulateHasOne = TRUE; // if TRUE will automatically populate has one fields
    						// this may be set at any time but will only have an effect when queries are run
	protected $initAutoPopulateHasOne = TRUE; // only set at instantiation

	protected $initFormat = array(); // used by clear to restore format to it's orignial value
							// this is only set at instantiation
    public $format = array(); // this array holds any fields that need to be formatted as well as the method to run them through, see the dataFormat library
    						// this may be changed at any time
	protected $initFormId = '';	// Initial state of form Id

    public $logQueries = NULL; // allows the enabling of query logging on a per model basis
    						// this may be changed at anytime but is setup in the constructor
	protected $initLogQueries = NULL; // this is set only at instantiation
    protected $lastRunQuery = ''; // this will store the last run query from an object
    						// this will be set anytime a query is run

    protected $defaultOrderBy = array(); // This can be specified as an array of fields to sort by if no other sorting or selection has occurred.
    							// this should default to ascending order
   								// this can be set only at runtime, if you need something different you can simply pass in an order by clause

    protected $resultRowCount = 0; // this is only set when a query is run
    protected $resultFieldCount = 0; // this is only set when a query is run
    protected $resultInsertId = NULL; // this is only set when an insert has occurred
    protected $resultAffectedRows = 0; // this is only set when an update or delete has occurred

    public $paged = array(); // this will hold the information returned by getPage()
							// this needs to be immutable from outside of the class
							// this needs to be set back to an empty array on non-paged queries

	protected $whereGroupStarted = FALSE; // If true, the next where statement will not be prefixed with an AND or OR. Used for query grouping
							// this is only set by calling one of the group methods

	protected $saveSuccess = FALSE; // this saves whether the last save for this object was successful or not
							// this will only be set on updates and inserts

	public static $tableFieldCache = array(); // this acts as a global cache to store table meta data in to reduce load on the database
	public static $transactionStarted = FALSE; // stores whether there's currently a transaction started or not

	/**
	 * Nagilum::__construct()
	 *
	 * @description - the constructor for the object, responsible for setting up the object and retrieving it if an id is passed back in
	 * @param mixed $id - the id of the primary key of this model
	 * @return - if id is passed in it will return the object with a record of the data for that id
	 */
	public function __construct($id = NULL)
	{
		// load the core components and libraries
		$this->CI =& EP_Controller::getInstance();
        $this->db =& $this->CI->db;
        $this->CI->aDBs['model'] =& $this->db;

        $this->inflector = $this->CI->load->helper('inflector');
        $this->form_validation = $this->CI->load->library('form_validation');
        $this->form_validation->CI =& $this->CI;
        $this->dataFormat = $this->CI->load->library('dataFormat');
		$this->input = $this->CI->input;

        // if the table isn't overriden and set by the class determine what table this model uses
        if ($this->table === NULL)
        {
        	$this->table = get_called_class();
        	$this->table = plural($this->table);
        }

        // setup the default model name if it isn't overriden by the model itself
        if ($this->model === NULL)
        {
        	$this->model = get_called_class();
        }

        // setup log queries if it hasn't been overridden by the class
        // first we check the environment
        if ($this->logQueries === NULL)
        {
        	if (isset($_SERVER['LOGQUERIES']))
        	{
        		$this->logQueries = $_SERVER['LOGQUERIES'];
        	}
        }
		// if the environment or the model don't set logquerires we'll set it to true only for development
        if ($this->logQueries === NULL)
        {
        	if ($this->CI->getEnvironment() === 'development')
        	{
        		$this->logQueries = TRUE;
        	} else {
        		$this->logQueries = FALSE;
        	}
        }

       	// get a list of the table's fields and store them for use when saving the object
        // we only want to do this for child classes though as the table for nagilum will never exist
        if ($this->table != 'nagila')
        {
        	// first we see if we have the table information cached in the global cache. If it is we can assume the table exists and retrieve the cached
       		// information instead of adding extra queries to the database

       		$cachedTableData = Nagilum::getCachedTableData($this->table);

       		if ($cachedTableData)
       		{
       			$this->tableMetaData = $cachedTableData;
       		} else {
       			// make sure that the table exists otherwise throw an error
       			if ($this->tableExists())
       			{
       				// get the tables metadata, and store it in the object and in the global Cache
       				$this->tableMetaData = $this->fieldData();
       				Nagilum::setCachedTableData($this->table, $this->tableMetaData);
       			} else {
        			// the table doesn't exist so we need to error out
        			throw new Exception('Table ' . $this->table . ' does not exist');
        		}
       		}

				$fields = $this->tableMetaData;

				// since the table exists we store the fields so that we know what to update/insert on saves
				foreach ($fields as $field)
				{
					// Populate the model's field array
					$this->tableFields[] = $field->name;
				}

				// ensure that the has one and has many arrays don't conflict with the tables fields
	   		foreach ($this->hasOne as $rel => $data)
	   		{
	   			if (array_search($rel, $this->tableFields) !== FALSE)
	   			{
	   				throw new Exception('Relationship name can\'t be the same as field in the table');
	   			}
	   		}

	   		foreach ($this->hasMany as $rel => $data)
	   		{
	   			if (array_search($rel, $this->tableFields) !== FALSE)
	   			{
	   				throw new Exception('Relationship name can\'t be the same as field in the table');
	   			}
	   		}

	        // store the initial has one and has many values for use with the clear method
	        $this->initHasMany = $this->hasMany;
	        $this->initHasOne = $this->hasOne;

	        // store the needed initial values of validation for use with the clear method
	        $this->initValidationRules = $this->validationRules;
	        $this->initSkipValidation = $this->skipValidation;

	        // store the initial values of the format and hooks for use with the clear method
	        $this->initFormat = $this->format;
	        $this->initPreSaveHooks = $this->preSaveHooks;
	        $this->initPostSaveHooks = $this->postSaveHooks;
	        $this->initPreResultHooks = $this->preResultHooks;
	        $this->initPostResultHooks = $this->postResultHooks;
	        $this->initPreDeleteHooks = $this->preDeleteHooks;

	        // store the initial values for autoinstantation of transactions and relationships
	        $this->initAutoPopulateHasMany = $this->autoPopulateHasMany;
	        $this->initAutoPopulateHasOne = $this->autoPopulateHasOne;
	        $this->initAutoTransaction = $this->autoTransaction;

	        // store the initial value of logQueries
	        $this->initLogQueries = $this->logQueries;

			//
			$this->initFormId = $this->formId;

			// if id was passed in we get the results for that row
	        if (!empty($id))
	        {
					$this->getBy($this->primaryKey, $id);
	        }

			// return this so that we can ensure that we get the updated query if an id was passed into the constructor
	        return $this;
        }
	}

	/**
	 * Nagilum::__get()
	 *
	 * @description - dynamic getter that handles accessing properties of the models data and rels array
	 * @param mixed $name - the name of the property that you're trying to access. This shouldn't ever be called directly
	 * @return mixed $result - the value of the property that you're trying to access or NULL if it doesn't exist
	 */
	public function &__get($name)
	{
		// First we check the data array for speed
		// see if the item exists within this objects data
		if (array_key_exists($name, $this->data))
		{
			$return = $this->data[$name];
			return $return;
		}

		// next we check the relationships since it's faster to check those than the table fields
		if (array_key_exists($name, $this->rels))
		{
			$return = $this->rels[$name];
			return $return; // may return an array of objects or an object depending on whether it's a has one or has many
		}

		// next we see if the object has been queried by checking it's id, we also make sure it's not a custom query
		if (!isset($this->data[$this->primaryKey]) || !$this->savable)
		{
			// this object doesn't have an id so we can't get the information from the database so we return NULL
			$return = NULL;
			return $return;
		}

		// the item has been queried so now we'll see if the data exists within the tables fields
		if (array_search($name, $this->tableFields) !== FALSE)
		{
			// the field exists within the table data so we can see if it was retrieved previously so we can avoid the extra database query
			if (array_key_exists($name, $this->stored))
			{
				$this->data[$name] = $this->stored[$name];
				return $this->data[$name];
			}

			// retrieve the data from the database
			$return = $this->retrieveDataField($name);
			return $return;
		}

		// next we need to see if the item exists in the hasOne or hasMany arrays and if so retrieve the item(s)
		if (isset($this->hasOne[$name]))
		{
			$this->getHasOne($name, TRUE);
			$return = $this->rels[$name];
			return $return;
		}

		if (isset($this->hasMany[$name]))
		{
			$this->getHasMany($name, TRUE);
			$return = $this->rels[$name];
			return $return;
		}

		// the item doesn't exist anywhere so return NULL using a variable so we can return it by reference
		$return = NULL;
		return $return;
	}

	/**
	 * Nagilum::__set()
	 *
	 * @description - dynamic setter that handles setting the objects data and rels
	 * @param mixed $name - the name of the property that you want to set value to
	 * @param mixed $value - the value that you want to set the property to
	 * @return void
	 */
	public function __set($name, $value)
    {
    	// is the object type a container or a data set
    	if ($this->cType === 'container'
				&& !((is_int($name) || empty($name))
					&& is_object($value)
					&& ($this->model == $value->getModelName())
				)
			)
    	{
    		throw new Exception('You can\'t set data directly on this object as it\'s a container of objects');
    	}

    	if ($this->cType === 'container')
    	{
    		$this->rels[] = $value;
    		return;
    	}

    	// if the value passed in is an object then we need to treat it as a relationship object
    	if (is_object($value))
    	{
    		if (empty($name))
    		{
    			throw new Exception('All models must be referenced by a relationship key');
    		}

			// ensure that the objects key isn't in the data array or in the tables fields so there's no conflicts when saving
			if (array_key_exists($name, $this->data) || array_search($name, $this->tableFields) !== FALSE)
			{
				throw new Exception('This key exists in the data of this object you can\'t set an object reference to a data value');
			}

			// ensure that the child is being passed in correctly
    		$this->validateSetChild($name, $value);

    		$this->rels[$name] = $value;
    		return;
    	}

		// the passed in value is a data item so we assign it to the data array as long as it doesn't conflict with a relationship name
		if (array_key_exists($name, $this->rels) || isset($this->hasOne[$name]) || isset($this->hasMany[$name]))
		{
			throw new Exception('This key exists in the relationships of this object. You can\'t set a data value to an object reference');
		}

		if (empty($name))
		{
			$this->data[] = $value;
			$this->dataChanged = TRUE;
			return;
		}

		// does the data item element already exist? If so we want to be sure that it's actually changed
		if (array_key_exists($name, $this->data))
		{
			if ($this->data[$name] !== $value)
			{
				$this->data[$name] = $value;
				$this->dataChanged = TRUE;
			}
			return;
		}

		// the data item doesn't exist anywhere so we're going to add it to the model and set changed to true
		$this->data[$name] = $value;
		$this->dataChanged = TRUE;
    }

    /**
     * Nagilum::__isset()
     *
	 * @description - dynamic isset that allows determining if an item is set in the data or rels arrays
     * @param mixed $name - the name of the property that you want to see if its set
     * @return bool $isset - returns whether the property is set on this object or not
     */
    public function __isset($name)
    {
		// we check the data array first for speed as it will be accessed most frequently
		// see if the requested variable is set in the data array
    	if (array_key_exists($name, $this->data))
    	{
    		return TRUE;
    	}

		// the data wasn't stored in the objects data array but it may be stored in the stored array from being in the database
    	if (array_key_exists($name, $this->stored))
    	{
    		return TRUE;
    	}

    	// the field wasn't in the stored array but it could have been a custom select so lets see if the object has an id (there was a query)
    	if (isset($this->data[$this->primaryKey]))
    	{
    		// the object has an id so we can assume that a query was run on it
    		if (array_search($name, $this->tableFields) === TRUE)
    		{
    			// the field was found so retrieve it from the database and return TRUE
    			if ($this->retrieveDataField($name) !== NULL)
    			{
	    			return TRUE;
    			}
    		}
    	}

    	// next we need to check the relationships
		// see if the requested variable is set in the relationships array
    	if (array_key_exists($name, $this->rels))
    	{
    		return TRUE;
    	}

    	// see if the requested variable is set in the has one or has many and hasn't been loaded in yet using lazy loading
    	if (isset($this->hasOne[$name]))
    	{
    		$this->getHasOne($name, TRUE);
    		return TRUE;
    	}

		// see if the requested variable is set in the has many and hasn't been loaded in yet using lazy loading
    	if ( isset($this->hasMany[$name]))
    	{
    		$this->getHasMany($name, TRUE);
    		return TRUE;
    	}

    	// the requested variable wasn't found so return false
    	return FALSE;
    }

    /**
     * Nagilum::__unset()
     *
	 * @description - dynamic unset that allows unsetting of the models data and rels
     * @param mixed $name - the name of the property that you want to unset
     * @return void
     */
    public function __unset($name)
    {
    	// next we check the data array for speed
    	if (array_key_exists($name, $this->data))
    	{
    		unset($this->data[$name]);
    		$this->recalculateHasChanged();
    		return;
    	}

    	// the object wasn't found in the data array so we'll next use the rels array
    	if (array_key_exists($name, $this->rels))
    	{
    		unset($this->rels[$name]);
    		return;
    	}
    }

    /**
     * Nagilum::__toString()
     *
	 * @description - allows direct printing of the object in a usable format
     * @return string $return - the print_r output of this object turned into an array
     */
    public function __toString()
    {
        $return = print_r($this->toArray(), TRUE);
        return $return;
    }

    /**
     * Nagilum::toArray()
     *
     * @description - returns the current object as an array
     * @return array $array - the array format of this object
     */
    public function toArray()
    {
    	// the array that will be returned
        $return = array();

        // loop through the data elements and set the keys accordingly
		foreach ($this->data as $key => $value)
        {
        	$return[$key] = $value;
        }

        // loop through the set relationship objects and add them to the array as arrays
        foreach ($this->rels as $key => $value)
        {
        	if ($value != NULL)
        	{
        		// the value is a Nagilum object so we need to convert it to an array as well
	        	$return[$key] = $value->toArray();
        	} else {
        		$return[$key] = NULL;
        	}
        }

        return $return;
    }

    /**
     * Nagilum::getIterator()
     *
     * @description - This is required by the arrayIterator interface to allow PHP to loop over the object
     * @return ArrayIterator $iterator - this object is used for looping over the object
     */
    public function getIterator()
    {
    	// returns an iterator that will be used in a foreach to loop through all data and objects
    	// we need to merge the data and relationship arrays so that everything is passed through to the foreach loop
    	$array = array_merge($this->data, $this->rels);

    	return new ArrayIterator($array);
    }

    /**
     * Nagilum::count()
     *
     * @description - this returns the total of the relationships and data elements of this model
     * @return int $count - this is the total number of data and relationship objects of this model
     */
    public function count()
    {
    	// initialize count to 0;
    	$count = 0;

		// data and rels are always arrays so we can safely use only count
    	$count += count($this->data);
    	$count += count($this->rels);

    	return $count;
    }

    /**
     * Nagilum::offsetExists()
     *
     * @description - this allows you to determine if an offset exists on a given model
     * @param mixed $name - the index that you want to see if it exists or not
     * @return bool $exists - TRUE if the object exists false otherwise
     */
    public function offsetExists($name)
    {
    	// call the isset method
    	return $this->__isset($name);
    }

    /**
     * Nagilum::offsetGet()
     *
     * @description - this returns a given offset on a model
     * @param mixed $name - the name of the index that you want to retrieve on the object
     * @return mixed $value - the value of the array at index $name that you're retrieving
     */
    public function offsetGet($name)
    {
    	// call the get method
    	return $this->__get($name);
    }

    /**
     * Nagilum::offsetSet()
     *
     * @description - this allows you to set a value to an offset on the model
     * @param mixed $name - the name of the index that you want to set
     * @param mixed $value - the value that you want to set index to
     * @return void
     */
    public function offsetSet($name, $value)
    {
    	// call the set method
    	$this->__set($name, $value);
    }

    /**
     * Nagilum::offsetUnset()
     *
     * @description - this allows you to remove an index from the model
     * @param mixed $name - the name of the index that you want to unset on this object
     * @return void
     */
    public function offsetUnset($name)
    {
    	// call the unset method
    	$this->__unset($name);
    }

    /**
     * Nagilum::toJson()
     *
     * @description - this returns the object as a json encoded array
     * @return string $json - the json encoded string representation of this object
     */
    public function toJson()
	{
		// return the object and all of it's children as a json encoded array
		$data = $this->toArray();
		$utf8Data = $this->utf8_encode_all($data);
		$json = json_encode($data);
		return $json;
	}

	/**
	 * Nagilum::utf8_encode_all()
	 *
	 * @description - this encodes all values of the array representation of this class so that they're propery JSON data elements
	 * @param mixed $data - the data to be utf8 encoded
	 * @return mixed $data - the utf8 encoded version of the data passed in
	 */
	protected function utf8_encode_all($data)
	{
		if (is_string($data))
		{
			return utf8_encode($data);
		}

		if (!is_array($data))
		{
			return $data;
		}

		$return = array();

		foreach ($data as $key => $value)
		{
			$return[$key] = $this->utf8_encode_all($value);
		}

		return $return;
	}

	/**
	 * Nagilum::tableExists()
	 *
	 * @description - This determines whether a table exists in the given database. Due to the way CI caches tables it's necessary to call switch
	 * 				database if the table is on another database.
	 * @param optional string $table - this is the name of the table you want to see if it exists by default it's the table of the current model
	 * @return bool $exists - TRUE/FALSE depending on whether the table exists or not
	 */
	public function tableExists($table = NULL)
    {
    	if ($table === NULL)
		{
			$table = $this->table;
		}

 		if (strpos($table, '.') !== FALSE)
 		{
 			$parts = explode('.', $table);
 			$this->db = $this->CI->switchDatabase($parts[0], TRUE);
 			$table = $parts[1];
 		}

    	return $this->db->table_exists($table);
    }

    /**
     * Nagilum::listFields()
     *
     * @description - This gets the list of fields for the given table
     * @param optional string $table - this is the table you want the fields for. By default it is the models table.
     * @return array $fields - an array of the fields in the table
     */
    protected function listFields($table = NULL)
	{
		if ($table === NULL)
		{
			$table = $this->table;
		}

		return $this->db->list_fields($table);
	}

	/**
	 * Nagilum::fieldData()
	 *
	 * @description - This returns the field and its metaData for the current model
	 * @param optional string $tableName - the table you want the field data for. By default it is the models table
	 * @return array $fieldData - an array of the metadata for the table
	 */
	protected function fieldData($tableName = NULL)
    {
    	if ($tableName === NULL)
    	{
    		$tableName = $this->table;
    	}

   		return $this->db->field_data($tableName);
    }

    /**
     * Nagilum::getFieldList()
     *
     * @description - This gets the existing fields for this table
     * @return array $fields - this returns an array of the models tableFields
     */
    public function getFieldList()
	{
		// returns the fields for this model
		return $this->tableFields;
	}

	/**
	 * Nagilum::getFieldMetaData()
	 *
	 * @description - This returns the metadata for the current models fields
	 * @return array $metaData - an array containing objects with this models fields and their metadata
	 */
	public function getFieldMetaData()
	{
		return $this->tableMetaData;
	}

	/**
	 * Nagilum::fieldExists()
	 *
	 * @description - This checks to see if a field exists on the given table
	 * @param string $fieldName - The field that you want to see if it exists
	 * @param optional string $tableName - The table that you're checking the fields on
	 * @return bool $exists - TRUE/FALSE depending on whether the field exists
	 */
	public function fieldExists($fieldName, $tableName = NULL)
    {
    	// this should use the current field list if tableName is null to reduce the queries
    	if ($tableName === NULL)
    	{
    		$tableName = $this->table;
    	}

    	return $this->db->field_exists($fieldName, $tableName);
    }

    /**
     * Nagilum::getTableName()
     *
     * @description - Returns the table name that is set for this model
     * @return string $table - the table name of the current model
     */
    public function getTableName()
	{
		return $this->table;
	}

	/**
	 * Nagilum::getModelName()
	 *
	 * @description - returns the current model name of this object
	 * @return string $model - the model name that is set on this object
	 */
	public function getModelName()
	{
		return $this->model;
	}

	/**
	 * Nagilum::setModelName()
	 *
	 * @description - allows you to change the model name of the current object. This is necessary in some relationships
	 * @param string $name - the name that you wish to set the object's model property to
	 * @return void
	 */
	public function setModelName($name)
	{
		$this->model = $name;
	}

	/**
	 * Nagilum::getParent()
	 *
	 * @description - Retrieves the parent model of this model if it exists
	 * @return Nagilum $parent - the parent of the current object
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Nagilum::setParent()
	 *
	 * @description - allows for setting of a models parent
	 * @param Nagilum $obj
	 * @return void
	 */
	public function setParent($obj)
	{
		$this->parent =& $obj;
	}

	/**
	 * Nagilum::getType()
	 *
	 * @description - Returns whether the current object is a data or container object
	 * @return string $type - the type of the current model
	 */
	public function getType()
	{
		return $this->cType;
	}

    /**
     * Nagilum::setAutoTransaction()
     *
     * @description - Sets whether to automatically wrap saves, updates, and deletes in a transaction
     * @param boolean $bool - whether to enable (TRUE) or disable (FALSE) automatic transaction handling
     * @return void
     */
    public function setAutoTransaction($bool)
    {
    	$this->autoTransaction = $bool;
    }

    /**
     * Nagilum::setAutoPopulateHasOne()
     *
     * @description - allows you to set whether to automatically populate hasOne relationships at run time
     * @param boolean $bool - whether to automatically populate the has one relationships
     * @return void
     */
    public function setAutoPopulateHasOne($bool)
    {
    	$this->autoPopulateHasOne = $bool;
    }

    /**
     * Nagilum::setAutoPopulateHasMany()
     *
     * @description - allows you to set whether to automatically populate hasMany relationships at run time
     * @param boolean $bool - whether to automatically populate the hasMany relationships
     * @return void
     */
    public function setAutoPopulateHasMany($bool)
    {
    	$this->autoPopulateHasMany = $bool;
    }

    /**
     * Nagilum::skipValidation()
     *
     * @description - allows you to set whether to skip validation or not at runtime
     * @param boolean $bool - Whether to skip validation or run it (TRUE = skip)
     * @return void
     */
    public function skipValidation($bool)
	{
		$this->skipValidation = $bool;
	}

    /**
     * Nagilum::retrieveDataField()
     *
     * @description - This retrieves a data field that exists but wasn't retrieved previously (usually because of a custom select)
     * @param string $name - the field to be retrieve
     * @return $mixed $value - the value of the field from the database or NULL if the field couldn't be retrieved
     */
    protected function retrieveDataField($name)
    {
    	// first we need to ensure that there are no build up query parts so that there's no conflicts
    	$this->db->_reset_select();

		// next we build up the query to retrieve the record
		$query = $this->db->select($name);
    	$query = $this->db->from($this->table);
    	$query = $this->db->where($this->primaryKey, $this->data[$this->primaryKey]);
    	$query = $this->db->get();

    	// ensure that we have at least one result otherwise return NULL
    	if ($query->num_rows() > 0)
    	{
    		$result = $query->row_array();
    		// just in case there's any oddities ensure that the field is set in the result otherwise return NULL
    		if (array_key_exists($name, $result))
    		{
    			$this->stored[$name] = $result[$name];
    			$this->data[$name] = $result[$name];
	    		return $result[$name];
    		}
    	}

    	return NULL;
    }

    /**
     * Nagilum::getSoftDeleteField()
     *
     * @description - returns the name of the field used for handling of soft deletion
     * @return string $field - the name of the field used for soft deleting on this table
     */
    public function getSoftDeleteField()
    {
    	return $this->softDeleteField;
    }

    /**
     * Nagilum::getHasOne()
     *
     * @description - This method is used to retrieve a hasOne relationship
     * @param string $name - the name of the relationship to retrieve
     * @param boolean $useAuto - whether to use the built in autoPopulateHasOne or to force a retrieval of this objects children
     * @return void
     */
    private function getHasOne($name, $useAuto)
    {
    	// see if the relationship exists otherwise throw an exception
    	if (!isset($this->hasOne[$name]))
    	{
    		throw new Exception('The child that is being instantiated: ' . $name . ' doesn\'t have an existing relationship with this object.');
    	}

    	// since this is a hasOne if the relationship is already in existence we return so we don't overwrite the existing data
    	if (isset($this->rels[$name]))
    	{
    		return;
    	}

    	$relationship = $this->hasOne[$name];

    	// by default we assume the class name is the same as the relationship name
    	$class = $name;
    	$relation = $name; // the key we're going to store the relationship as

		// relationship overridden?
    	if (isset($relationship['class']))
    	{
    		$class = $relationship['class'];
    	}

		// create an instance of the child class so we can determine its details
    	$temp = new $class();
    	$childTable = $temp->getTableName();
    	$childPK = $temp->getPrimaryKey();

    	// by default we assume we're not using a join table but are using this models table since we're in a has one relationship
    	$joinTable = FALSE;
    	$table = $this->table;

		// joinTable overridden?
    	if (isset($relationship['joinTable']))
    	{
    		$table = $relationship['joinTable'];
    		if ($table != $this->table)
    		{
    			$joinTable = TRUE;
    		}
    	}

    	// by default we assume that the joinField is the relationship name followed by _id
    	$joinField = $name . '_id';

		// joinField overridden?
    	if (isset($relationship['joinField']))
    	{
    		$joinField = $relationship['joinField'];
    	}


    	// by default we assume that the childsJoinField is it's primary key
		$childJoinField = $childPK;

		// childJoinField overridden?
		if (isset($relationship['childJoinField']))
		{
			$childJoinField = $relationship['childJoinField'];
		}

		//TODO Shouldn't this be using getSoftDeleteField()?
		$isDeletedExists = $temp->fieldExists('is_deleted');

		if(isset($relationship['ignore_deleted']))
		{
			$ignoreDeleted = $relationship['ignore_deleted'];
		} else {
			$ignoreDeleted = TRUE;
		}

		// next we need to build up the query
		if ($joinTable === FALSE)
		{
			// only used in the case of self joins and only allowed on Has One relationships
			if($table == $childTable)
			{
				$asTableName = 'ParentTable';
				$query = $this->db->from($table . ' AS ' . $asTableName);
			} else {
				$asTableName = $table;
				$query = $this->db->from($table);
			}
			$query = $this->db->select($childTable . '.*', FALSE);
			if ($this->primaryKey)
			{
				$query = $this->db->where($asTableName . '.' . $this->primaryKey, $this->data[$this->primaryKey]);
			}
			if ($ignoreDeleted && $isDeletedExists)
			{
				$query = $this->db->where($childTable . '.' . $temp->getSoftDeleteField(), 0);
			}
			if(isset($relationship['order_by']))
			{
				$order_by = $relationship['order_by'];
				foreach($order_by as $oField => $oDir)
				{
					$query = $this->db->order_by($oField, $oDir);
				}
			} else {
				$query = $this->db->order_by($childTable . "." . $temp->getPrimaryKey(), 'ASC');
			}
			$query = $this->db->join($childTable, $asTableName . '.' . $joinField . ' = ' . $childTable . '.' . $childJoinField, 'left outer');
		} else {
			$query = $this->db->select($childTable . '.*', FALSE);
			$query = $this->db->from($table);
			$query = $this->db->where($table . '.' . $joinField, $this->data[$this->primaryKey]);
			if ($ignoreDeleted && $isDeletedExists)
			{
				$query = $this->db->where($childTable . '.' . $temp->getSoftDeleteField(), 0);
			}
			if(isset($relationship['order_by']))
			{
				$order_by = $relationship['order_by'];
				foreach($order_by as $oField => $oDir)
				{
					$query = $this->db->order_by($oField, $oDir);
				}
			} else {
				$query = $this->db->order_by($childTable . "." . $temp->getPrimaryKey(), 'ASC');
			}
			$query = $this->db->join($childTable, $table . '.' . $childJoinField . ' = ' . $childTable . '.' . $temp->getPrimaryKey(), 'left outer');
		}

		if(isset($relationship['whereClause']))
		{
			$whereClause = $relationship['whereClause'];
			$whereBool = TRUE;
			if($whereClause['clause'] == 'NULL')
			{
				$whereBool = FALSE;
			}
			$this->db->where($whereClause['field'], $whereClause['clause'], $whereBool);
		}
		if(isset($relationship['whereClause2']))
		{
			$whereClause = $relationship['whereClause2'];
			$whereBool = TRUE;
			if($whereClause['clause'] == 'NULL')
			{
				$whereBool = FALSE;
			}
			$this->db->where($whereClause['field'], $whereClause['clause'], $whereBool);
		}
		if(isset($relationship['limitClause']))
		{
			$limitClause = $relationship['limitClause'];
			$this->db->limit($limitClause['rows'], $limitClause['offset']);
		}

		$query = $this->db->get();

		if ($query->num_rows() == 0)
		{
			log_message('debug', 'There Was No Has One Relationship Results Found');
			$this->rels[$relation] = NULL;
			return FALSE;
		}

		if ($query->num_rows() > 1)
		{
			log_message('debug', 'More than one child class was found');
		}

		$result = $query->row_array();

		$child = new $class();
		$child->callPreResultHooks();
		$result = $child->formatFields($result);
		$child->buildFromResultArray($result);
		$child->callPostResultHooks();
		$child->setModelName($relation);
		$child->setParent($this);
		$child->getChildrenAll($useAuto);
		$this->rels[$relation] = $child;
    }

    /**
     * Nagilum::getPrimaryKey()
     *
     * @description - retrieves the primary key used for this model
     * @return string $primaryKey - the primary key setup on this model
     */
    public function getPrimaryKey()
    {
    	return $this->primaryKey;
    }

    /**
     * Nagilum::makeContainer()
     *
     * @description - sets the current object as a container (used in result sets)
     * @return void
     */
    public function makeContainer()
    {
    	$this->cType = 'container';
    }

	public function setTypeToContainer()
	{
		$this->cType = 'container';
	}

	public function addRecordToContainer($objects)
    {
    	if($this->cType != 'container')
    	{
			throw new Exception('You can only use this method on a container');
		}

		if(is_object($objects))
		{
			$this->rels[] = $objects;
		} else {
			foreach($objects as $object)
			{
				$this->rels[] = $object;
			}
		}

		return $this;
    }

    /**
     * Nagilum::getHasMany()
     *
     * @description - This method is used to retrieve a hasMany relationship
     * @param string $name - the name of the relationship to retrieve
     * @param boolean $useAuto - whether to use the built in autoPopulateHasMany or to force a retrieval of this objects children
     * @return void
     */
    private function getHasMany($name, $useAuto)
    {
    	// see if the relationship exists otherwise throw an exception
    	if (!isset($this->hasMany[$name]))
    	{
    		throw new Exception('The child that is being instantiated: ' . $name . ' doesn\'t have an existing relationship with this object.');
    	}

    	$relationship = $this->hasMany[$name];

    	// by default we assume the class name is the same as the relationship name
    	$class = $name;
    	$relation = $name; // the key we're going to store the relationship as

		// relationship overridden?
    	if (isset($relationship['class']))
    	{
    		$class = $relationship['class'];
    	}

		// create an instance of the child class so we can determine its details
    	$temp = new $class();

    	// by default we assume we're not using a join table but are using this child models table since we're in a has many relationship
    	$joinTable = FALSE;
    	$table = $temp->getTableName();
    	$childTable = $table; // in case there is a join table

		// joinTable overridden?
    	if (isset($relationship['joinTable']))
    	{
    		$table = $relationship['joinTable'];
    		if ($table != $temp->getTableName())
    		{
    			$joinTable = TRUE;
    		}
    	}

    	// by default we assume that the joinField is the current class's model name followed by _id
    	$joinField = $this->model . '_id';

		// joinField overridden?
    	if (isset($relationship['joinField']))
    	{
    		$joinField = $relationship['joinField'];
    	}

    	// by default we assume that the childsJoinField is it's model name followed by _id
		$childJoinField = $temp->getModelName() . '_id';

		// childJoinField overridden?
		if (isset($relationship['childJoinField']))
		{
			$childJoinField = $relationship['childJoinField'];
		}

		$isDeletedExists = $temp->fieldExists('is_deleted');

		if(isset($relationship['ignore_deleted']))
		{
			$ignoreDeleted = $relationship['ignore_deleted'];
		} else {
			$ignoreDeleted = TRUE;
		}

		// next we need to build up the query
		if ($joinTable == FALSE)
		{
			$query = $this->db->select($table . '.*', FALSE);
			$query = $this->db->from($table);
			if ($this->primaryKey)
			{
				$query = $this->db->where($table . '.' . $joinField, $this->data[$this->primaryKey]);
			}
			if ($ignoreDeleted && $isDeletedExists)
			{
				$query = $this->db->where($table . '.' . $temp->getSoftDeleteField(), 0);
			}
			if(isset($relationship['order_by']))
			{
				$order_by = $relationship['order_by'];
				foreach($order_by as $oField => $oDir)
				{
					$query = $this->db->order_by($oField, $oDir);
				}
			} else {
				if ($primaryKey = $temp->getPrimaryKey())
				{
					$query = $this->db->order_by($table . "." . $primaryKey, 'ASC');
				}
			}
			if (isset($relationship['group_by']))
			{
				$group_by = $relationship['group_by'];
				foreach ($group_by as $gField)
				{
					$query = $this->db->group_by($gField);
				}
			}
			if (isset($relationship['limit']))
			{
				$query = $this->db->limit($relationship['limit']);
			}
		} else {
			$query = $this->db->select($childTable . '.*', FALSE);
			$query = $this->db->from($table);
			if ($this->primaryKey)
			{
				$query = $this->db->where($table . '.' . $joinField, $this->data[$this->primaryKey]);
			}
			if ($ignoreDeleted && $isDeletedExists)
			{
				$query = $this->db->where($childTable . '.' . $temp->getSoftDeleteField(), 0);
			}
			if(isset($relationship['order_by']))
			{
				$order_by = $relationship['order_by'];
				foreach($order_by as $oField => $oDir)
				{
					$query = $this->db->order_by($oField, $oDir);
				}
			} else {
				if ($primaryKey = $temp->getPrimaryKey())
				{
					$query = $this->db->order_by($childTable . "." . $temp->getPrimaryKey(), 'ASC');
				}
			}
			if (isset($relationship['group_by']))
			{
				$group_by = $relationship['group_by'];
				foreach ($group_by as $gField)
				{
					$query = $this->db->group_by($gField);
				}
			}
			if (isset($relationship['limit']))
			{
				$query = $this->db->limit($relationship['limit']);
			}
			$query = $this->db->join($childTable, $table . '.' . $childJoinField . ' = ' . $childTable . '.' . $temp->getPrimaryKey(), 'left outer');
		}

		if(isset($relationship['whereClause']))
		{
			$whereClause = $relationship['whereClause'];
			$whereBool = TRUE;
			if($whereClause['clause'] == 'NULL')
			{
				$whereBool = FALSE;
			}
			$this->db->where($whereClause['field'], $whereClause['clause'], $whereBool);
		}
		if(isset($relationship['whereClause2']))
		{
			$whereClause = $relationship['whereClause2'];
			$whereBool = TRUE;
			if($whereClause['clause'] == 'NULL')
			{
				$whereBool = FALSE;
			}
			$this->db->where($whereClause['field'], $whereClause['clause'], $whereBool);
		}
		if(isset($relationship['limitClause']))
		{
			$limitClause = $relationship['limitClause'];
			$this->db->limit($limitClause['rows'], $limitClause['offset']);
		}

		$query = $this->db->get();

		if ($query->num_rows() == 0)
		{
			log_message('debug', 'There Was No Has Many Relationship Results Found');
			$this->rels[$relation] = NULL;
			return FALSE;
		}

		$result = $query->result_array();

		$primary = $temp->getPrimaryKey();
		if (!isset($this->rels[$relation]))
		{
			$all = TRUE;
		} else {
			$all = FALSE;
		}

		$container = new $class();
		$container->setModelName($relation);
		$container->makeContainer();
		$this->rels[$relation] = $container;

		foreach ($result as $row)
		{
			$current = ($primary) ? $row[$primary]: FALSE;
//			$current = $row[$primary];
			if ($all)
			{
				$child = new $class();
				$child->callPreResultHooks();
				$row = $child->formatFields($row);
				$child->buildFromResultArray($row);
				$child->callPostResultHooks();
				$child->setModelName($relation);
				$child->setParent($this);
				$child->getChildrenAll($useAuto);
				$this->rels[$relation][] = $child;
				continue;
			}

			$found = FALSE;

			foreach ($this->relations[$relation] as $obj)
			{
				if ($current == $obj->id)
				{
					$found = TRUE;
				}
			}

			if (!$found)
			{
				$child = new $class();
				$child->callPreResultHooks();
				$row = $child->formatFields($row);
				$child->buildFromResultArray($row);
				$child->callPostResultHooks();
				$child->setModelName($relation);
				$child->setParent($this);
				$child->getChildrenAll($useAuto);
				$this->rels[$relation][] = $child;
			}
		}
    }

    /**
     * Nagilum::validateSetChild()
     *
     * @description - this is used to ensure that a model added to a collection is of the appropriate type
     * @param string $name - the name of the Nagilum object you're adding
     * @param Nagilum $value - the object you're adding
     * @return void
     */
    private function validateSetChild($name, $value)
    {
		// be sure that the object is a model
		if (!$value instanceof Nagilum)
		{
			throw new Exception('Any objects set on a model must be an instance of a model');
		}

    	// ensure that the model is being set to the correct key so we know how to handle it
		if ($value->getModelName() != $name)
		{
			throw new Exception('You\'re trying to assign a reference to a Model to a key that doesn\'t match that model\'s model property (' . $name . ')');
		}

		// be sure that there is a relationship to the model that is being saved into this class so that we know how to handle it
		if (!isset($this->hasOne[$name]) && !isset($this->hasMany[$name]))
		{
			throw new Exception('There is no relationship to the model that you\'re trying to add to this class');
		}
    }

    /**
     * Nagilum::clear()
     *
     * @description - resets an object completely to its initial state - like you instantiated a new object
     * @return void
     */
    public function clear()
    {
    	// clear the primary data and relationships
    	$this->data = array();
    	$this->rels = array();
    	$this->stored = array();

		// reset the stored metadata to it's default values
    	$this->dataChanged = FALSE;
    	$this->savable = TRUE;
    	$this->resultFieldCount = 0;
    	$this->resultRowCount = 0;
    	$this->resultAffectedRows = 0;
    	$this->resultInsertId = NULL;

    	// clear the data related to validation
    	$this->errors = array();
    	$this->valid = FALSE;
    	$this->skipValidation = $this->initSkipValidation;
    	$this->validated = FALSE;
    	$this->validationRules = $this->initValidationRules;

    	// clear the pagination array
    	$this->paged = array();

    	// clear the relationships
    	$this->hasOne = $this->initHasOne;
    	$this->hasMany = $this->initHasMany;

    	// clear the hooks and format arrays
    	$this->format = $this->initFormat;
    	$this->preSaveHooks = $this->initPreSaveHooks;
    	$this->postSaveHooks = $this->initPostSaveHooks;
    	$this->preResultHooks = $this->initPreResultHooks;
    	$this->postResultHooks = $this->initPostResultHooks;
    	$this->preDeleteHooks = $this->initPreDeleteHooks;

		//
		$this->formId = $this->initFormId;

    	// clear the autoTransaction and autoRelationship fields
    	$this->autoTransaction = $this->initAutoTransaction;
    	$this->autoPopulateHasOne = $this->initAutoPopulateHasOne;
    	$this->autoPopulateHasMany = $this->initAutoPopulateHasMany;

    	// clear the logQueries and other db items
    	$this->logQueries = $this->initLogQueries;
    	$this->lastRunQuery = '';
    	$this->whereGroupStarted = FALSE;
    	$this->saveSuccess = FALSE;
    	$this->db->_reset_select();

    	// reset the objects type
    	$this->cType = 'data';
    }

    /**
     * Nagilum::resetObject()
     *
     * @description - resets the object to a base state as if the query alone hadn't been run
     * @return void
     */
    public function resetObject()
	{
		// clear the primary data and relationships
    	$this->data = array();
    	$this->rels = array();
    	$this->stored = array();

    	// reset the stored metadata to it's default values
    	$this->dataChanged = FALSE;
    	$this->savable = TRUE;
    	$this->resultFieldCount = 0;
    	$this->resultRowCount = 0;
    	$this->resultAffectedRows = 0;
    	$this->resultInsertId = NULL;

    	// clear the data related to validation
    	$this->errors = array();
    	$this->valid = FALSE;
    	$this->validated = FALSE;

    	// clear the pagination object
    	$this->paged = array();

    	// clear the saveSuccess and other db items
    	$this->saveSuccess = FALSE;

    	// reset the objects type
    	$this->cType = 'data';
	}

	/**
	 * Nagilum::hasChanged()
	 *
	 * @description - returns whether the current object has changed from its initial state
	 * @return boolean $hasChanged - whether the object has changed
	 */
	public function hasChanged()
    {
	   	return $this->dataChanged;
    }

    /**
     * Nagilum::hasChangedAll()
     *
     * @description - returns whether the current object or any of it's children have changed from their initial states
     * @return boolean $hasChanged - whether the object or it's children have changed
     */
    public function hasChangedAll()
    {
    	if ($this->hasChanged())
    	{
    		return TRUE;
    	}
    	foreach ($this->rels as $obj)
    	{
    		if ($obj->hasChangedAll())
    		{
    			return TRUE;
    		}
    	}

    	return FALSE;
    }

	/**
	 * Nagilum::hasChangedField()
	 *
	 * @description - returns whether a field from the current object has changed from its initial state
	 * @param string $field - the field to check whether it's changed
	 * @param string $format - the format of the field to account for strange cases like dates
	 * @return boolean $hasChanged - whether the field has changed
	 */
	public function hasChangedField($field = NULL, $format = NULL)
    {
    	if (empty($field))
    	{
    		return false;
		}

		if (!isset($this->stored[$field])){
			if (!isset($this->data[$field])){
				return false;
			} else {
				return true;
			}
		}

		if (empty($format))
    	{
    		return ($this->stored[$field] != $this->data[$field]);
   		}

   		if ($format == 'date')
   		{
   			$storedDate = new DateTime($this->stored[$field]);
   			$dataDate = new DateTime($this->data[$field]);

   			return ($storedDate->format('Y-m-d') != $dataDate->format('Y-m-d'));
		}

		if ($format == 'time')
		{
			$storedTime = new DateTime($this->stored[$field]);
			$dataTime = new DateTime($this->data[$field]);

			return ($storedTime->format('H:i:s') != $dataTime->format('H:i:s'));
		}

		if ($format == 'datetime')
		{
   			$storedDate = new DateTime($this->stored[$field]);
   			$dataDate = new DateTime($this->data[$field]);

   			return ($storedDate != $dataDate);
		}

		return ($this->stored[$field] != $this->data[$field]);

    }

    /**
     * Nagilum::getChangedFields()
     *
     * @description - Returns an array of the fields that have changed from their initial state
     * @return array $fields - an array of the fields that have changed
     */
    public function getChangedFields()
    {
    	$changed = array();

    	if ($this->dataChanged == FALSE)
    	{
    		return $changed;
    	}

    	foreach ($this->data as $key => $value)
    	{
    		if (!array_key_exists($key, $this->stored))
    		{
    			$changed[] = array('key' => $key, 'old' => NULL, 'new' => $this->data[$key]);
    			continue;
    		}
    		if ($value != $this->stored[$key])
    		{
    			$changed[] = array('key' => $key, 'old' => $this->stored[$key], 'new' => $this->data[$key]);
    		}
    	}

    	return $changed;
    }

    /**
     * Nagilum::getChangedFieldsAll()
     *
     * @description - gets an array of the fields that have changed from their initial state for the current object and it's children
     * @return array $fields - an array of the fields that have changed from this object and it's children
     */
    public function getChangedFieldsAll()
    {
    	$changed = $this->getChangedFields();

    	foreach ($this->rels as $key => $obj)
    	{
    		$objChanged = $obj->getChangedFieldsAll();
    		if (count($objChanged) > 0)
    		{
    			$changed[$key] = $objChanged;
    		}
    	}

    	return $changed;
    }

    /**
     * Nagilum::recalculateHasChanged()
     *
     * @description - recalculates whether the object has changed or not
     * @return boolean $return - returns true if the object has changed from its initial state
     */
    protected function recalculateHasChanged()
    {
    	foreach ($this->data as $key => $value)
    	{
    		if (!array_key_exists($key, $this->stored))
    		{
    			continue;
    		}
    		if ($value !== $this->stored[$key])
    		{
    			return TRUE;
    		}
    	}
    }

    /**
     * Nagilum::setPreSaveHook()
     *
     * @param mixed $method - the method to be called in the same format as the call_user_func method
     * @param mixed $params - any parameters that you want passed back into your object
     * @return void
     */
    public function setPreSaveHook($method, $params = NULL)
    {
    	if (!is_callable($method))
    	{
    		$methodName = print_r($method, TRUE);
    		throw new Exception('The supplied method' . $methodName . 'is not callable');
    	}
        $this->preSaveHooks[] = array('method' => $method, 'params' => $params);
    }

    /**
     * Nagilum::callPreSaveHooks()
     *
     * @description - calls the presave hooks for this object
     * @return void
     */
    protected function callPreSaveHooks()
    {
    	$this->preSaveHook();

        foreach ($this->preSaveHooks as $callBack)
        {
            if ($callBack['params'] != NULL)
            {
                call_user_func($callBack['method'], $this, $callBack['params']);
            } else {
                call_user_func($callBack['method'], $this);
            }
        }
    }

    /**
     * Nagilum::preSaveHook()
     *
     * @description - the base presave hook method for this model
     * @return void
     */
    protected function preSaveHook()
    {
        return;
    }

    /**
     * Nagilum::setPostSaveHook()
     *
     * @param mixed $method - the method to be called in the same format as the call_user_func method
     * @param mixed $params - any parameters that you want passed back into your object
     * @return void
     */
    public function setPostSaveHook($method, $params = NULL)
    {
    	if (!is_callable($method))
    	{
    		$methodName = print_r($method, TRUE);
    		throw new Exception('The supplied method' . $methodName . 'is not callable');
    	}
        $this->postSaveHooks[] = array('method' => $method, 'params' => $params);
    }

    /**
     * Nagilum::callPostSaveHooks()
     *
     * @description - calls the postsave hooks for this object
     * @return void
     */
    protected function callPostSaveHooks()
    {
    	$this->postSaveHook();

        foreach ($this->postSaveHooks as $callBack)
        {
            if ($callBack['params'] != NULL)
            {
                call_user_func($callBack['method'], $this, $callBack['params']);
            } else {
                call_user_func($callBack['method'], $this);
            }
        }
    }

    /**
     * Nagilum::postSaveHook()
     *
     * @description - the base postSave hook method for this model
     * @return void
     */
    protected function postSaveHook()
    {
        return;
    }

	     /**
     * Nagilum::setPostInsertHook()
     *
     * @param mixed $method - the method to be called in the same format as the call_user_func method
     * @param mixed $params - any parameters that you want passed back into your object
     * @return void
     */
    public function setPostInsertHook($method, $params = NULL)
    {
    	if (!is_callable($method))
    	{
    		$methodName = print_r($method, TRUE);
    		throw new Exception('The supplied method' . $methodName . 'is not callable');
    	}
        $this->postInsertHooks[] = array('method' => $method, 'params' => $params);
    }

    /**
     * Nagilum::callPostInsertHooks()
     *
     * @description - calls the postsave hooks for this object
     * @return void
     */
    protected function callPostInsertHooks()
    {
			$this->postInsertHook();

        foreach ($this->postInsertHooks as $callBack)
        {
            if ($callBack['params'] != NULL)
            {
                call_user_func($callBack['method'], $this, $callBack['params']);
            } else {
                call_user_func($callBack['method'], $this);
            }
        }
    }

    /**
     * Nagilum::postInsertHook()
     *
     * @description - the base postSave hook method for this model
     * @return void
     */
    protected function postInsertHook()
    {
        return;
    }

	 	     /**
     * Nagilum::setPostUpdateHook()
     *
     * @param mixed $method - the method to be called in the same format as the call_user_func method
     * @param mixed $params - any parameters that you want passed back into your object
     * @return void
     */
    public function setPostUpdateHook($method, $params = NULL)
    {
    	if (!is_callable($method))
    	{
    		$methodName = print_r($method, TRUE);
    		throw new Exception('The supplied method' . $methodName . 'is not callable');
    	}
        $this->postUpdateHooks[] = array('method' => $method, 'params' => $params);
    }

    /**
     * Nagilum::callPostUpdateHooks()
     *
     * @description - calls the postsave hooks for this object
     * @return void
     */
    protected function callPostUpdateHooks()
    {
			$this->postUpdateHook();

        foreach ($this->postUpdateHooks as $callBack)
        {
            if ($callBack['params'] != NULL)
            {
                call_user_func($callBack['method'], $this, $callBack['params']);
            } else {
                call_user_func($callBack['method'], $this);
            }
        }
    }

    /**
     * Nagilum::postUpdateHook()
     *
     * @description - the base postSave hook method for this model
     * @return void
     */
    protected function postUpdateHook()
    {
        return;
    }

    /**
     * Nagilum::setPreResultHook()
     *
     * @param mixed $method - the method to be called in the same format as the call_user_func method
     * @param mixed $params - any parameters that you want passed back into your object
     * @return void
     */
    public function setPreResultHook($method, $params = NULL)
    {
    	if (!is_callable($method))
    	{
    		$methodName = print_r($method, TRUE);
    		throw new Exception('The supplied method' . $methodName . 'is not callable');
    	}
        $this->preResultHooks[] = array('method' => $method, 'params' => $params);
    }

    /**
     * Nagilum::callPreResultHooks()
     *
     * @description - calls the preResult hooks for this object
     * @return void
     */
    protected function callPreResultHooks()
    {
    	$this->preResultHook();

        foreach ($this->preResultHooks as $callBack)
        {
            if ($callBack['params'] != NULL)
            {
                call_user_func($callBack['method'], $this, $callBack['params']);
            } else {
                call_user_func($callBack['method'], $this);
            }
        }
    }

    /**
     * Nagilum::preResultHook()
     *
     * @description - the base preResult hook method for this model
     * @return void
     */
    protected function preResultHook()
    {
        return;
    }

    /**
     * Nagilum::setPostResultHook()
     *
     * @param mixed $method - the method to be called in the same format as the call_user_func method
     * @param mixed $params - any parameters that you want passed back into your object
     * @return void
     */
    public function setPostResultHook($method, $params = NULL)
    {
    	if (!is_callable($method))
    	{
    		$methodName = print_r($method, TRUE);
    		throw new Exception('The supplied method' . $methodName . 'is not callable');
    	}
        $this->postResultHooks[] = array('method' => $method, 'params' => $params);
    }

    /**
     * Nagilum::callPostResultHooks()
     *
     * @description - calls the postResult hooks for this object
     * @return void
     */
    protected function callPostResultHooks()
    {
    	$this->postResultHook();

        foreach ($this->postResultHooks as $callBack)
        {
            if ($callBack['params'] != NULL)
            {
                call_user_func($callBack['method'], $this, $callBack['params']);
            } else {
                call_user_func($callBack['method'], $this);
            }
        }
    }

    /**
     * Nagilum::postResultHook()
     *
     * @description - the base postResult hook method for this model
     * @return void
     */
    protected function postResultHook()
    {
        return;
    }

    /**
     * Nagilum::setPreDeleteHook()
     *
     * @param mixed $method - the method to be called in the same format as the call_user_func method
     * @param mixed $params - any parameters that you want passed back into your object
     * @return void
     */
    public function setPreDeleteHook($method, $params = NULL)
    {
    	if (!is_callable($method))
    	{
    		$methodName = print_r($method, TRUE);
    		throw new Exception('The supplied method' . $methodName . 'is not callable');
    	}
        $this->postSaveHooks[] = array('method' => $method, 'params' => $params);
    }

    /**
     * Nagilum::callPreDeleteHooks()
     *
     * @description - calls the preDelete hooks for this object
     * @return void
     */
    protected function callPreDeleteHooks()
    {
    	$this->preDeleteHook();

        foreach ($this->preDeleteHooks as $callBack)
        {
            if ($callBack['params'] != NULL)
            {
                call_user_func($callBack['method'], $this, $callBack['params']);
            } else {
                call_user_func($callBack['method'], $this);
            }
        }
    }

    /**
     * Nagilum::preDeleteHook()
     *
     * @description - the base preDelete hook method for this model
     * @return void
     */
    protected function preDeleteHook()
    {
        return;
    }

    /**
     * Nagilum::hasChild()
     *
     * @descrption - Determines whether the data model exists within this models hasOne or hasMany arrays
     * @param Nagilum $obj - the object you want to see whether it's a child of this object or not
     * @return boolean $isChild - TRUE if the model name exists as a key in the hasOne or hasMany relationships
     */
    public function hasChild(Nagilum $obj)
	{
		// sees if there is a relationship defined between the passed in item and the current object
		$model = $obj->getModelName();
		if ($this->hasOne[$model])
		{
			return TRUE;
		}

		if ($this->hasMany[$model])
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Nagilum::autoTransactionBegin()
	 *
	 * @description - Responsible for handling the automatic starting of transactions
	 * @return void
	 */
	public function autoTransactionBegin()
	{
		if ($this->autoTransaction && Nagilum::$transactionStarted === FALSE)
		{
			$this->transactionStart();
			Nagilum::$transactionStarted = TRUE;
		}
	}

	/**
	 * Nagilum::autoTransactionComplete()
	 *
	 * @description - responsible for automatically ending the transaction and returning success or failure
	 * @return boolean $success - whether the transaction completed successfully or not
	 */
	public function autoTransactionComplete()
	{
		if ($this->autoTransaction && Nagilum::$transactionStarted === TRUE)
		{
			$this->transactionComplete();
			Nagilum::$transactionStarted = FALSE;

			// if the query wasn't successful throw an exception
			if ($this->db->trans_status() === FALSE)
			{
			    return FALSE;
			} else {
				return TRUE;
			}
		}
	}

	/**
	 * Nagilum::transactionBegin()
	 *
	 * @description - Please see CI User Guide for details on this method
	 * @param bool $testMode - whether to set the transaction to test mode
	 * @return bool $result - whether the transaction was started or not
	 */
	public function transactionBegin($testMode = FALSE)
	{
		return $this->db->trans_begin($testMode);
	}

	/**
	 * Nagilum::transactionStart()
	 *
	 * @description - Please see CI User Guide for details on this method
	 * @param bool $testMode - whether to set the transaction to test mode
	 * @return
	 */
	public function transactionStart($testMode = FALSE)
	{
		$this->db->trans_start($testMode);
	}

	/**
	 * Nagilum::transactionComplete()
	 *
	 * @description - Please see CI User Guide for details on this method
	 * @return bool
	 */
	public function transactionComplete()
	{
		return $this->db->trans_complete();
	}

	/**
	 * Nagilum::transactionStrict()
	 *
	 * @description - Please see CI User Guide for details on this method
	 * @param bool $mode - whether or not to turn transactions to strict mode
	 * @return void
	 */
	public function transactionStrict($mode = TRUE)
	{
		$this->db->trans_strict($mode);
	}

	/**
	 * Nagilum::transactionStatus()
	 *
	 * @description - Please see CI User Guide for details on this method
	 * @return bool $status - whether the transaction was successful or not
	 */
	public function transactionStatus()
	{
		return $this->db->_trans_status;
	}

	/**
	 * Nagilum::transactionOff()
	 *
	 * @description - allows you to turn transactions off
	 * @return void
	 */
	public function transactionOff()
	{
		$this->db->trans_enabled = FALSE;
	}

	/**
	 * Nagilum::transactionRollback()
	 *
	 * @description - Please see CI User Guide for details on this method
	 * @return bool $result - whether the transaction was rolled back or not
	 */
	public function transactionRollback()
	{
		return $this->db->trans_rollback();
	}

	/**
	 * Nagilum::transactionCommit()
	 *
	 * @description - Please see CI User Guide for details on this method
	 * @return bool $result - whether the commit was successful or not
	 */
	public function transactionCommit()
	{
		return $this->db->trans_commit();
	}

	/**
	 * Nagilum::query()
	 *
	 * @description - This allows you to run a manual query with or without binds
	 * @param string $sql - The sql you want to execute
	 * @param $mixed $binds - an array of data that you want bound to the ?'s within the query
	 * @return Nagilum $row - returns a single row of data as a Nagilum object
	 */
	public function query($sql, $binds = FALSE)
	{
		$this->resetObject();

		$query = $this->db->query($sql, $binds);

		// store the last run query and log it if needed
		$this->logQuery();

		if (is_bool($query))
		{
			$this->resultInsertId = $this->db->insert_id();
			$this->resultAffectedRows = $this->db->affected_rows();
			return $query;
		}

		$num_rows = $query->num_rows();
		$this->resultRowCount = $num_rows;
		$this->resultFieldCount = $query->num_fields();

		if ($query->num_rows > 0)
		{
			$this->data = $query->row_array();
			$this->stored = $this->data;
			$this->savable = FALSE;
		}

		return $this;
	}

	/**
	 * Nagilum::queryAll()
	 *
	 * @description - This allows you to run a manual query with or without binds
	 * @param string $sql - The sql you want to execute
	 * @param $mixed $binds - an array of data that you want bound to the ?'s within the query
	 * @return Nagilum $resul - returns the result data as a Nagilum object
	 */
	public function queryAll($sql, $binds = FALSE)
	{
		$this->resetObject();

		$query = $this->db->query($sql, $binds);

		// store the last run query and log it if needed
		$this->logQuery();

		if (is_bool($query))
		{
			$this->resultInsertId = $this->db->insert_id();
			$this->resultAffectedRows = $this->db->affected_rows();
			return $query;
		}

		$num_rows = $query->num_rows();

		$this->resultRowCount = $num_rows;
		$this->resultFieldCount = $query->num_fields();

		if ($query->num_rows > 0)
		{

			$this->cType = 'data';
			$this->savable = FALSE;

			foreach ($query->result_array() as $row)
			{
				$obj = $this->getCopy();
				$obj->resetObject();
				$obj->buildFromResultArray($row);

				$this->rels[] = $obj;
			}
			$this->cType = 'container';
		}

		return $this;
	}

	/**
	 * Nagilum::numRows()
	 *
	 * @description - returns the number of results from the last query on this object
	 * @return int $rows - the number of rows from the last query
	 */
	public function numRows()
	{
		return $this->resultRowCount;
	}

	/**
	 * Nagilum::numFields()
	 *
	 * @description - returns the number of fields from the last query
	 * @return int $fields - the number of fields in the last queries result set
	 */
	public function numFields()
	{
		return $this->resultFieldCount;
	}

	/**
	 * Nagilum::protectIdentifiers()
	 *
	 * @description - protects mysql identifiers such as table names
	 * @param mixed $item - the item to be protected
	 * @return string $protected - the protected string of the identifiers passed in
	 */
	public function protectIdentifiers($item)
	{
		return $this->db->protect_identifiers($item);
	}

	/**
	 * Nagilum::escape()
	 *
	 * @description - escapes a value for prevention of SQL injections
	 * @param mixed $str - the value to be escaped
	 * @return mixed $str - the escaped value
	 */
	public function escape($str)
	{
		return $this->db->escape($str);
	}

	/**
	 * Nagilum::escapeStr()
	 *
	 * @description - escapes a string for prevention of SQL injections
	 * @param string $str - the string to be escaped
	 * @return string $str - the escaped string
	 */
	public function escapeStr($str)
	{
		return $this->db->escape_string($str);
	}

	/**
	 * Nagilum::escapeLikeStr()
	 *
	 * @description - escapes a string for prevention of SQL injections (when the string is to be used in a like)
	 * @param string $str - the string to be escaped
	 * @return string $str - the escaped string
	 */
	public function escapeLikeStr($str)
	{
		return $this->db->escape_like_str($str);
	}

	/**
	 * Nagilum::insertID()
	 *
	 * @description - returns the insert id of a new record
	 * @return int $insertID - the id of the last insert
	 */
	public function insertID()
	{
		return $this->resultInsertId;
	}

	/**
	 * Nagilum::affectedRows()
	 *
	 * @description - returns the affected rows of the query
	 * @return int $affectedRows - the number of rows affected by the last update / delete
	 */
	public function affectedRows()
	{
		return $this->resultAffectedRows;
	}

	/**
	 * Nagilum::countAll()
	 *
	 * @description - returns the total number of records in a table
	 * @param optional string $table - the table that you want the number of rows from
	 * @return int $rows - the number of rows in the specified table (this models table by default)
	 */
	public function countAll($table = NULL)
	{
		if ($table === NULL)
		{
			$table = $this->table;
		}

		$this->db->count_all($table);
	}

	/**
	 * Nagilum::limit()
	 *
	 * @description - allows you to set a limit clause on a query
	 * @param mixed $value - the number of rows to return
	 * @param string $offset - the offset of the first row
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function limit($value, $offset = '')
	{
		$this->db->limit($value, $offset);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::foundRows()
	 *
	 * @description - gets the total number of rows after user SQL_CALC_FOUND_ROWS in previous query
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function foundRows()
	{
		$total = $this->db->query('SELECT FOUND_ROWS() as `count`');

		return $total->row()->count;
	}

	/**
	 * Nagilum::addTableName()
	 *
	 * @description - This adds the table name to the fields in AR methods
	 * @param string $field - The field to have the table name added to
	 * @return string $field - The field with the table name added
	 */
	public function addTableName($field)
	{
		// only add table if the field doesn't contain an open parentheses
		if (preg_match('/[\.\(]/', $field) == 0)
		{
			// split string into parts, add field
			$field_parts = explode(',', $field);
			$field = '';
			foreach ($field_parts as $part)
			{
				if ( ! empty($field))
				{
					$field .= ', ';
				}
				$part = ltrim($part);
				// handle comparison operators on where
				$subparts = explode(' ', $part, 2);
				if ($subparts[0] == '*' || in_array($subparts[0], $this->tableFields))
				{
					$field .= $this->table  . '.' . $part;
				}
				else
				{
					$field .= $part;
				}
			}
		}
		return $field;
	}

	/**
	 * Nagilum::select()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param string $select
	 * @param bool $escape
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function select($select = '*', $escape = TRUE)
	{
		if ($escape !== FALSE) {
			if (!is_array($select)) {
				$select = $this->addTableName($select);
			} else {
				$updated = array();
				foreach ($select as $sel) {
					$updated = $this->addTableName($sel);
				}
				$select = $updated;
			}
		}
		$this->db->select($select, $escape);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::selectMax()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param string $select
	 * @param string $alias
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function selectMax($select, $alias)
	{
		if (empty($select))
		{
			throw new Exception('You must provide a field to select max on');
		}

		if (empty($alias))
		{
			throw new Exception('You must include an alias for the field name');
		}

		$this->db->select_max($this->addTableName($select), $alias);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::selectMin()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param string $select
	 * @param string $alias
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function selectMin($select, $alias)
	{
		if (empty($select))
		{
			throw new Exception('You must provide a field to select min on');
		}

		if (empty($alias))
		{
			throw new Exception('You must include an alias for the field name');
		}

		$this->db->select_min($this->addTableName($select), $alias);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::selectAvg()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param string $select
	 * @param string $alias
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function selectAvg($select, $alias)
	{
		if (empty($select))
		{
			throw new Exception('You must provide a field to select avg on');
		}

		if (empty($alias))
		{
			throw new Exception('You must include an alias for the field name');
		}

		$this->db->select_avg($this->addTableName($select), $alias);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::selectSum()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param string $select
	 * @param string $alias
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function selectSum($select, $alias)
	{
		if (empty($select))
		{
			throw new Exception('You must provide a field to select sum on');
		}

		if (empty($alias))
		{
			throw new Exception('You must include an alias for the field name');
		}

		$this->db->select_sum($this->addTableName($select), $alias);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::buildWhere()
	 *
	 * @description - Builds the active record where clause
	 * @param mixed $key - the where clauses
	 * @param mixed $value - the value to match with the key(s)
	 * @param string $type - whether this is an and or an or
	 * @param mixed $escape - whether to escape the passed in key(s) and value
	 * @return Nagilum $this - the current object for method chaining
	 */
	protected function buildWhere($key, $value = NULL, $type = 'AND ', $escape = NULL)
	{
		if (!is_array($key))
		{
			$key = array($key => $value);
		}
		foreach ($key as $k => $v)
		{
			$new_k = $this->addTableName($k);
			if ($new_k != $k)
			{
				$key[$new_k] = $v;
				unset($key[$k]);
			}
		}

		$type = $this->getPrependType($type);

		$this->db->_where($key, $value, $type, $escape);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::where()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $key
	 * @param mixed $value
	 * @param bool $escape
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function where($key, $value = NULL, $escape = TRUE)
	{
		return $this->buildWhere($key, $value, 'AND ', $escape);
	}

	/**
	 * Nagilum::orWhere()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $key
	 * @param mixed $value
	 * @param bool $escape
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function orWhere($key, $value = NULL, $escape = TRUE)
	{
		return $this->buildWhere($key, $value, 'OR ', $escape);
	}

	/**
	 * Nagilum::buildWhereIn()
	 *
	 * @description - This method builds up the whereIn methods for active record
	 * @param mixed $key
	 * @param mixed $values
	 * @param bool $not
	 * @param string $type
	 * @return Nagilum $this - the current object for method chaining
	 */
	protected function buildWhereIn($key = NULL, $values = NULL, $not = FALSE, $type = 'AND ')
	{
		$type = $this->getPrependType($type);

	 	$this->db->_where_in($key, $values, $not, $type);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::whereIn()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $key
	 * @param mixed $values
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function whereIn($key = NULL, $values = NULL)
	{
		return $this->buildWhereIn($key, $values);
	}

	/**
	 * Nagilum::orWhereIn()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function orWhereIn($key = NULL, $values = NULL)
	{
		return $this->buildWhereIn($key, $values, FALSE, 'OR ');
	}

	/**
	 * Nagilum::whereNotIn()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function whereNotIn($key = NULL, $values = NULL)
	{
		return $this->buildWhereIn($key, $values, TRUE);
	}

	/**
	 * Nagilum::orWhereNotIn()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function orWhereNotIn($key = NULL, $values = NULL)
	{
		return $this->buildWhereIn($key, $values, TRUE, 'OR ');
	}

	/**
	 * Nagilum::buildLike()
	 *
	 * @description - This builds up the like clauses for AR queries
	 * @param mixed $field - the field to be selected on
	 * @param string $match - the expression to match
	 * @param string $type - whether it's an AND or OR
	 * @param string $side - the side for the % signs
	 * @param string $not - whether this is a not method
	 * @param bool $no_case - for case insensitive searches
	 * @return Nagilum $this - the current object for method chaining
	 */
	protected function buildLike($field, $match = '', $type = 'AND ', $side = 'both', $not = '', $no_case = FALSE)
	{
		if ( ! is_array($field))
		{
			$field = array($field => $match);
		}

		foreach ($field as $k => $v)
		{
			$new_k = $this->addTableName($k);
			if ($new_k != $k)
			{
				$field[$new_k] = $v;
				unset($field[$k]);
			}
		}

		// Taken from CodeIgniter's Active Record because (for some reason)
		// it is stored separately from normal where statements.

		foreach ($field as $k => $v)
		{
			if ($no_case)
			{
				$k = 'UPPER(' . $this->db->protect_identifiers($k) .')';
				$v = strtoupper($v);
			}
			$f = "$k $not LIKE";

			$v = $this->escapeLikeStr($v);

			if ($side == 'before')
			{
				$m = "%{$v}";
			}
			elseif ($side == 'after')
			{
				$m = "{$v}%";
			}
			else
			{
				$m = "%{$v}%";
			}

			$this->buildWhere($f, $m, $type, TRUE);
		}

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::like()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $field
	 * @param string $match
	 * @param string $side
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function like($field, $match = '', $side = 'both')
	{
		return $this->buildLike($field, $match, 'AND ', $side);
	}

	/**
	 * Nagilum::orLike()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $field
	 * @param string $match
	 * @param string $side
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function orLike($field, $match = '', $side = 'both')
	{
		return $this->buildLike($field, $match, 'OR ', $side);
	}

	/**
	 * Nagilum::notLike()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $field
	 * @param string $match
	 * @param string $side
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function notLike($field, $match = '', $side = 'both')
	{
		return $this->buildLike($field, $match, 'AND ', $side, 'NOT');
	}

	/**
	 * Nagilum::orNotLike()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $field
	 * @param string $match
	 * @param string $side
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function orNotLike($field, $match = '', $side = 'both')
	{
		return $this->buildLike($field, $match, 'OR ', $side, 'NOT');
	}

	/**
	 * Nagilum::iLike()
	 *
	 * @description - Please See The CI Documentation For Details On This Method (case insensitive like)
	 * @param mixed $field
	 * @param string $match
	 * @param string $side
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function iLike($field, $match = '', $side = 'both')
	{
		return $this->buildLike($field, $match, 'AND ', $side, '', TRUE);
	}

	/**
	 * Nagilum::orILike()
	 *
	 * @description - Please See The CI Documentation For Details On This Method (case insensitive or_like)
	 * @param mixed $field
	 * @param string $match
	 * @param string $side
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function orILike($field, $match = '', $side = 'both')
	{
		return $this->buildLike($field, $match, 'OR ', $side, '', TRUE);
	}

	/**
	 * Nagilum::notILike()
	 *
	 * @description - Please See The CI Documentation For Details On This Method (case insensitive not_like)
	 * @param mixed $field
	 * @param string $match
	 * @param string $side
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function notILike($field, $match = '', $side = 'both')
	{
		return $this->buildLike($field, $match, 'AND ', $side, '', TRUE);
	}

	/**
	 * Nagilum::orNotILike()
	 *
	 * @description - Please See The CI Documentation For Details On This Method (case insensitive or_not_like)
	 * @param mixed $field
	 * @param string $match
	 * @param string $side
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function orNotILike($field, $match = '', $side = 'both')
	{
		return $this->buildLike($field, $match, 'OR ', $side, 'NOT', TRUE);
	}

	/**
	 * Nagilum::buildHaving()
	 *
	 * @description - Builds the having clause for AR queries
	 * @param mixed $key - the field of the query
	 * @param string $value - the value of the having clause
	 * @param string $type - whether this is an AND or OR
	 * @param bool $escape - whether to escape the values
	 * @return Nagilum $this - the current object for method chaining
	 */
	protected function buildHaving($key, $value = '', $type = 'AND ', $escape = TRUE)
	{
		$this->db->_having($this->addTableName($key), $value, $type, $escape);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::having()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $key
	 * @param string $value
	 * @param bool $escape
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function having($key, $value = '', $escape = TRUE)
	{
		return $this->buildHaving($key, $value, 'AND ', $escape);
	}

	/**
	 * Nagilum::orHaving()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $key
	 * @param string $value
	 * @param bool $escape
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function orHaving($key, $value = '', $escape = TRUE)
	{
		return $this->buildHaving($key, $value, 'OR ', $escape);
	}

	/**
	 * Nagilum::groupBy()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $field
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function groupBy($field)
	{
		$this->db->group_by($this->addTableName($field));

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::orderBy()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $orderby
	 * @param string $direction
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function orderBy($orderby, $direction = '')
	{
		$this->db->order_by($this->addTableName($orderby), $direction);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::join()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param mixed $table - the table to join on
	 * @param mixed $cond - the join condition
	 * @param string $type - the type of join
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function join($table, $cond, $type = '')
	{
		$this->db->join($table, $cond, $type);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::getPrependType()
	 *
	 * @description - this gets the prepend type for Where clauses when using AR groups
	 * @param mixed $type
	 * @return
	 */
	protected function getPrependType($type)
	{
		if ($this->whereGroupStarted)
		{
			$type = '';
			$this->whereGroupStarted = FALSE;
		}
		return $type;
	}

	/**
	 * Nagilum::from()
	 *
	 * @description - Sets the table that the query will run from
	 * @return void
	 */
	protected function from()
	{
		$this->db->from($this->table);
	}

	/**
	 * Nagilum::groupStart()
	 *
	 * @description - Builds up the groups for grouping on Where clauses
	 * @param string $not - whether this is a not grouping
	 * @param string $type - whether this is an AND or OR group
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function groupStart($not = '', $type = 'AND ')
	{
		$type = $this->getPrependType($type);

		$prefix = (count($this->db->ar_where) == 0) ? '' : $type;
		$this->db->ar_where[] = $prefix . $not .  ' (';
		$this->whereGroupStarted = TRUE;
		return $this;
	}

	/**
	 * Nagilum::orGroupStart()
	 *
	 * @description - Or Group start for active record Where clause grouping
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function orGroupStart()
	{
		return $this->groupStart('', 'OR ');
	}

	/**
	 * Nagilum::notGroupStart()
	 *
	 * @description - Not group start for active record Where clause grouping
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function notGroupStart()
	{
		return $this->groupStart('NOT ', 'AND ');
	}

	/**
	 * Nagilum::orNotGroupStart()
	 *
	 * @description - Or not group start for active record where clause grouping
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function orNotGroupStart()
	{
		return $this->groupStart('NOT ', 'OR ');
	}

	/**
	 * Nagilum::groupEnd()
	 *
	 * @description - Method to end open groups in where clause grouping
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function groupEnd()
	{
		$this->db->ar_where[] = ')';
		$this->whereGroupStarted = FALSE;
		return $this;
	}

	/**
	 * Nagilum::distinct()
	 *
	 * @description - Please See The CI Documentation For Details On This Method
	 * @param bool $value
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function distinct($value = TRUE)
	{
		$this->db->distinct($value);

		// For method chaining
		return $this;
	}

	/**
	 * Nagilum::setFields()
	 *
	 * @description - sets the fields for use in inserting and deleting
	 * @return - void
	 */
	protected function setFields()
	{
		$savableFields = $this->tableFields;
		if (($pos = array_search($this->createdAtField, $savableFields)) !== FALSE)
		{
			unset($savableFields[$pos]);
		}

		if (($pos = array_search($this->createdByField, $savableFields)) !== FALSE)
		{
			unset($savableFields[$pos]);
		}

		if (($pos = array_search($this->updatedAtField, $savableFields)) !== FALSE)
		{
			unset($savableFields[$pos]);
		}

		if (($pos = array_search($this->updatedByField, $savableFields)) !== FALSE)
		{
			unset($savableFields[$pos]);
		}

		if (($pos = array_search($this->softDeleteField, $savableFields)) !== FALSE)
		{
			unset($savableFields[$pos]);
		}

		if (($pos = array_search($this->primaryKey, $savableFields)) !== FALSE)
		{
			unset($savableFields[$pos]);
		}

		foreach ($savableFields as $field)
		{
			if (array_key_exists($field, $this->data))
			{
				$this->db->set($field, $this->data[$field]);
			}
		}
	}

	/**
	 * Nagilum::saveAsNew()
	 *
	 * @description - Takes the current result object and saves it as a new object
	 * @return void
	 */
	public function saveAsNew()
	{
		if (isset($this->data[$this->primaryKey]))
		{
			unset($this->data[$this->primaryKey]);
		}

		$this->save();
/*
TODO: Fix this
		foreach ($this->rels as $obj)
		{
			$this->saveRelationship($obj);
		}
*/
	}

	/**
	 * Nagilum::delete()
	 *
	 * @description - Deletes the current record (with softDelete if the table has the field)
	 * @return void
	 */
	public function delete()
	{
		if (!isset($this->data[$this->primaryKey]))
		{
			$this->callPreDeleteHooks();
			$this->resetObject();
			return;
		}
		if ($this->fieldExists($this->softDeleteField))
		{
			$this->callPreDeleteHooks();
			$startedTransaction = FALSE;
			if ($this->autoTransaction && !Nagilum::$transactionStarted)
			{
				$startedTransaction = TRUE;
				$this->autoTransactionBegin();
			}
			$this->db->_reset_select();

			// add the updated at and updated by fields if they exist
			$this->updatedAt();
			$this->updatedBy();

			$this->db->set($this->softDeleteField, 1);
			$this->db->where($this->table . '.' . $this->primaryKey, $this->data[$this->primaryKey]);
			$this->db->update($this->table);

			$this->resultAffectedRows = $this->db->affected_rows();

			if ($startedTransaction)
			{
				$result = $this->autoTransactionComplete();
			}

			$this->resetObject();
			$this->logQuery();
		} else {
			$this->hardDelete();
			return;
		}
		$this->resetObject();
	}

	/**
	 * Nagilum::undelete()
	 *
	 * @description - Undeletes the current record (with softDelete if the table has the field)
	 * @return void
	 */
	public function undelete()
	{
		/* TODO add undelete hooks:
		if (!isset($this->data[$this->primaryKey]))
		{
			$this->callPreDeleteHooks();
			$this->resetObject();
			return;
		}
		 */
		if ($this->fieldExists($this->softDeleteField))
		{
			//$this->callPreDeleteHooks(); //TODO add preundeletehook
			$startedTransaction = FALSE;
			if ($this->autoTransaction && !Nagilum::$transactionStarted)
			{
				$startedTransaction = TRUE;
				$this->autoTransactionBegin();
			}
			$this->db->_reset_select();

			$this->setFields();

			// add the updated at and updated by fields if they exist
			$this->updatedAt();
			$this->updatedBy();

			$this->db->set($this->softDeleteField, 0);
			$this->db->where($this->table . '.' . $this->primaryKey, $this->data[$this->primaryKey]);
			$this->db->update($this->table);

			$this->resultAffectedRows = $this->db->affected_rows();

			if ($startedTransaction)
			{
				$result = $this->autoTransactionComplete();
			}

			$this->resetObject();
			$this->logQuery();
		}
		$this->resetObject();
	}

	/**
	 * Nagilum::whereNotDeleted()
	 *
	 * @description - Used to automatically handle retrieving of non soft deleted records when the soft delete field exists
	 * @return
	 */
	public function whereNotDeleted()
	{
		if ($this->fieldExists($this->softDeleteField))
		{
			$this->db->where($this->softDeleteField, 0);
		}
	}

	/**
	 * Nagilum::deleteAll()
	 *
	 * @description - Deletes the current record and all of it's children using softDelete where possible
	 * @return void
	 */
	public function deleteAll()
	{
		$startedTransaction = FALSE;
		if ($this->autoTransaction && !Nagilum::$transactionStarted)
		{
			$startedTransaction = TRUE;
			$this->autoTransactionBegin();
		}
		foreach ($this->rels as $key => $obj)
		{
			$obj->deleteAll();
		}
		if ($this->cType === 'data')
		{
			$this->delete();
		}
		if ($startedTransaction)
		{
			$result = $this->autoTransactionComplete();
		}
	}

	/**
	 * Nagilum::hardDelete()
	 *
	 * @description - Forces a hard delete on the current record regardless of the softDelete field
	 * @return void
	 */
	public function hardDelete()
	{
		$this->callPreDeleteHooks();
		if (!isset($this->data[$this->primaryKey]))
		{
			$this->resetObject();
			return;
		}

		$startedTransaction = FALSE;
		if ($this->autoTransaction && !Nagilum::$transactionStarted)
		{
			$startedTransaction = TRUE;
			$this->autoTransactionBegin();
		}

		$this->db->where($this->primaryKey, $this->data[$this->primaryKey]);
		$this->db->delete($this->table);
		$this->logQuery();

		$this->resultAffectedRows = $this->db->affected_rows();

		if ($startedTransaction)
		{
			$result = $this->autoTransactionComplete();
		}

		$this->resetObject();
		return;
	}

	/**
	 * Nagilum::hardDeleteAll()
	 *
	 * @description - Forces a hard delete on the current record and its children
	 * @return
	 */
	public function hardDeleteAll()
	{
		$startedTransaction = FALSE;
		if ($this->autoTransaction && !Nagilum::$transactionStarted)
		{
			$startedTransaction = TRUE;
			$this->autoTransactionBegin();
		}
		foreach ($this->rels as $key => $obj)
		{
			$obj->hardDeleteAll();
		}
		$this->hardDelete();
		if ($startedTransaction)
		{
			$result = $this->autoTransactionComplete();
		}
	}

	/**
	 * Nagilum::getBy()
	 *
	 * @description - shorthand method for returning an object based on a single fields value (used for constructor)
	 * @param string $field - the field to return based on
	 * @param mixed $value - the value the field has to equal
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function getBy($field, $value)
	{
		$this->where($field, $value);
		$this->row();


		return $this;
	}

	/**
	 * Nagilum::getPaged()
	 *
	 * @description - Returns a paged result set to make pagination easier
	 * @param integer $page - the current page you're on
	 * @param integer $pageSize - the number of records per page
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function getPaged($page = 1, $pageSize = 40)
	{
		if ($page < 1)
		{
			$page = 1;
		}
		if ($pageSize < 1)
		{
			$pageSize = 40;
		}

		$tempDB =clone $this->db;
		//$countQuery->db = $tempDB;

		$offset = $pageSize * ($page - 1);

		// for performance, we clear out the select AND the order by statements on the count query,
		// since they aren't necessary and might slow down the query.
		$tempDB->from($this->getTableName());

		$total = $tempDB->count_all_results();

		$lastRow = $pageSize * floor($total / $pageSize);
		$totalPages = ceil($total / $pageSize);

		if ($offset >= $lastRow)
		{
			// make sure it doesn't go over the last page
			$offset = $lastRow;
			$page = $totalPages;
		}

		$page = (int)$page;
		$totalPages = (int)$totalPages;

		$this->limit($pageSize, $offset);
		$this->result();

		$this->paged['pageSize'] = $pageSize;
		$this->paged['itemsOnPage'] = $this->count();
		$this->paged['currentPage'] = $page;
		$this->paged['currentRow'] = $offset;
		$this->paged['totalRows'] = $total;
		$this->paged['lastRow'] = $lastRow;
		$this->paged['totalPages'] = $totalPages;
		$this->paged['hasPrevious'] = $offset > 0;
		$this->paged['previousPage'] = max(1, $page - 1);
		$this->paged['previousRow'] = max(0, $offset - $pageSize);
		$this->paged['hasNext'] = $page < $totalPages;
		$this->paged['nextPage'] = min($totalPages, $page + 1);
		$this->paged['nextRow'] = min($lastRow, $offset + $pageSize);

		return $this;
	}

	/**
	 * Nagilum::getPagedGB()
	 *
	 * @description - Returns a paged result set to make pagination easier for models with group_bys
	 * @param integer $page - the current page you're on
	 * @param integer $pageSize - the number of records per page
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function getPagedGB($page = 1, $pageSize = 40)
	{
		if ($page < 1)
		{
			$page = 1;
		}
		if ($pageSize < 1)
		{
			$pageSize = 40;
		}

		$tempDB =clone $this->db;
		//$countQuery->db = $tempDB;

		$offset = $pageSize * ($page - 1);

		// for performance, we clear out the select AND the order by statements on the count query,
		// since they aren't necessary and might slow down the query.
		$tempDB->from($this->getTableName());

		if (count($tempDB->ar_groupby)){
			$tempDB->ar_select = array();
			$tempDB->distinct();
			$tempDB->select(implode(', ',$tempDB->ar_groupby));
			$tempDB->ar_groupby = array();
		} else {
			$tempDB->select($this->getTableName().'.*');
		}

		$tempDB2 =clone $tempDB;
		$tempDB2->_reset_select();
		$query = $tempDB2->query('SELECT COUNT(*) AS total FROM ('.$tempDB->_compile_select().') AS query');
		$res = $query->row_array();
		$total = $res['total'];

		$lastRow = $pageSize * floor($total / $pageSize);
		$totalPages = ceil($total / $pageSize);

		if ($offset >= $lastRow)
		{
			// make sure it doesn't go over the last page
			$offset = $lastRow;
			$page = $totalPages;
		}

		$page = (int)$page;
		$totalPages = (int)$totalPages;

		$this->limit($pageSize, $offset);
		$this->result();

		$this->paged['pageSize'] = $pageSize;
		$this->paged['itemsOnPage'] = $this->count();
		$this->paged['currentPage'] = $page;
		$this->paged['currentRow'] = $offset;
		$this->paged['totalRows'] = $total;
		$this->paged['lastRow'] = $lastRow;
		$this->paged['totalPages'] = $totalPages;
		$this->paged['hasPrevious'] = $offset > 0;
		$this->paged['previousPage'] = max(1, $page - 1);
		$this->paged['previousRow'] = max(0, $offset - $pageSize);
		$this->paged['hasNext'] = $page < $totalPages;
		$this->paged['nextPage'] = min($totalPages, $page + 1);
		$this->paged['nextRow'] = min($lastRow, $offset + $pageSize);

		return $this;
	}

	/**
	 * Nagilum::row()
	 *
	 * @description - Retrieves a single result set AR style
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function row()
	{
		$this->limit(1);

		$query = $this->get();

		$num_rows = $query->num_rows();

		$this->resultRowCount = $num_rows;
		$this->resultFieldCount = $query->num_fields();

		if ($query->num_rows() > 0)
		{
			$row = $query->row_array();

			$row = $this->formatFields($row);

			$this->buildFromResultArray($row);
			$this->getChildrenAll(TRUE);
		} else {
			$this->data = array();
		}

		// call the post Result Hooks
		$this->callPostResultHooks();

		return $this;
	}

	/**
	 * Nagilum::handleDefaultOrderBy()
	 *
	 * @description - handles default ordering by
	 * @return void
	 */
	protected function handleDefaultOrderBy()
	{
		if (empty($this->defaultOrderBy))
		{
			return;
		}

		// only add the items if there isn't an existing order_by
		if (empty($this->db->ar_orderby))
		{
			foreach ($this->defaultOrderBy as $key => $value) {
				if (is_int($key)) {
					$key = $value;
					$value = '';
				}
				$key = $this->addTableName($key);
				$this->orderBy($key, $value);
			}
		}
	}

	/**
	 * Nagilum::result()
	 *
	 * @description - Returns multiple result sets AR style
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function result()
	{
		$query = $this->get();

		$num_rows = $query->num_rows();

		$this->resultRowCount = $num_rows;
		$this->resultFieldCount = $query->num_fields();

		foreach ($query->result_array() as $row)
		{
			$row = $this->formatFields($row);

			$obj = $this->getCopy();
			$obj->resetObject();
			$obj->buildFromResultArray($row);
			$obj->getChildrenAll(TRUE);

			// call the post Result Hooks on the children as they're the only ones with data
			$obj->callPostResultHooks();

			$this->rels[] = $obj;
		}

		$this->savable = FALSE;
		$this->cType = 'container';

		return $this;
	}

	/**
	 * Nagilum::saveSuccessful()
	 *
	 * @description - Returns whether tha last save was successful or not
	 * @return
	 */
	public function saveSuccessful()
	{
		return $this->saveSuccess;
	}

	/**
	 * Nagilum::saveSuccessfulAll()
	 *
	 * @description - Returns whether the last save was successful for this model and all of its children (for use with saveAll)
	 * @return
	 */
	public function saveSuccessfulAll()
	{
		if ($this->cType === 'container')
		{
			$success = TRUE;
		} else {
			$success = $this->saveSuccess;
		}

		if (!$success)
		{
			return FALSE;
		}

		foreach ($this->rels as $child)
		{
			$child->saveSuccessfulAll();
		}

		return TRUE;
	}

	/**
	 * Nagilum::save()
	 *
	 * @description - Saves the current model (does an insert or delete as appropriate based on the primary key for this model)
	 * @return bool $success - whether the save was successful
	 */
	public function save()
	{
		if (!$this->savable)
		{
			$this->saveSuccess = FALSE;
			return TRUE;
		}

		$this->callPreSaveHooks();

		if (!$this->skipValidation)
		{
			$this->runValidation();
			if (!$this->valid)
			{
				return FALSE;
			}
		}

		$startedTransaction = FALSE;
		if ($this->autoTransaction && !Nagilum::$transactionStarted)
		{
			$startedTransaction = TRUE;
			$this->autoTransactionBegin();
		}

		if (!empty($this->data[$this->primaryKey]))
		{
			$result = $this->update();
		} else {
			$result = $this->insert();
		}

		$this->stored = $this->data;

		if ($startedTransaction)
		{
			$result = $this->autoTransactionComplete();
		}

		$this->callPostSaveHooks();

		return $result;
	}

	/**
	 * Nagilum::saveAll()
	 *
	 * @description - Saves the current model and all of its children
	 * @return bool $result - whether the saveAll was successful
	 */
	public function saveAll()
	{
		$result = TRUE;

		$startedTransaction = FALSE;
		if ($this->autoTransaction && !Nagilum::$transactionStarted)
		{
			$startedTransaction = TRUE;
			$this->autoTransactionBegin();
		}

		if ($this->cType === 'data')
		{
			$result = $this->save();
		}

		foreach ($this->rels as $obj)
		{
			$result2 = $obj->saveAll();
			if ($result === TRUE)
			{
				$result = $result2;
			}
		}

		if ($startedTransaction)
		{
			$result = $this->autoTransactionComplete();
		}

		return $result;
	}

	/**
	 * Nagilum::formatFields()
	 *
	 * @description - Performs formatting of result data
	 * @param array $fields - the fields to be formatted
	 * @return array $fields - the formatted fields
	 */
	public function formatFields($fields)
	{
		foreach ($this->format as $field => $rules)
		{
			if (array_key_exists($field, $fields))
			{
				$rules = explode('|', $rules);
				foreach ($rules as $rule)
				{
					if (strpos($rule, 'callback_') !== FALSE)
					{
						// calling a method of this model
						$method = str_replace('callback_', '', $rule);
						$fields[$field] = $this->$method($fields[$field]);
					} else {
						// calling a method of the dataFormat class
						$method = $rule;
						$fields[$field] = $this->dataFormat->$method($fields[$field]);
					}
				}
			}

			if($field == '*')
			{
				$rules = explode('|', $rules);
				foreach ($rules as $rule)
				{
					if (strpos($rule, 'callback_') !== FALSE)
					{
						// calling a method of this model
						$method = str_replace('callback_', '', $rule);
						$fields = $this->$method($fields);
					} else {
						// calling a method of the dataFormat class
						$method = $rule;
						$fields = $this->dataFormat->$method($fields);
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Nagilum::runValidation()
	 *
	 * @description - runs the validation over an object prior to saving it
	 * @return bool $valid - whether the object is valid or not
	 */
	protected function runValidation()
	{
		$base = $this->formId;//'nagilum';

		if(count($this->validationRules) === 0)
		{
			$this->valid = TRUE;
			return TRUE;
		}

	//	// add the data items to the post array
	//	foreach ($this->data as $key => $val) {
	//		$_POST[$base][$key] = $val;
	//	}

		$this->form_validation->currentModel = $this;

		foreach ($this->validationRules as $rule)
		{
			if (strpos($rule['input'], '[') !== FALSE AND preg_match_all('/\[(.*?)\]/', $rule['input'], $matches))
			{
				// Note: Due to a bug in current() that affects some versions
				// of PHP we can not pass function call directly into it
				$x = explode('[', $rule['input']);
				$_POST[$x[0]][rtrim($x[1],']')] = $this->$rule['field'];
			} else {
				$_POST[$rule['input']] = $this->$rule['field'];
			}
			$this->form_validation->set_rules( $rule['input'], $rule['label'], $rule['rules']);
		}

		if ($this->form_validation->run($base) === FALSE)
		{
			$this->validated = TRUE;
			$this->valid = FALSE;
			$errors = $this->form_validation->getErrorsArray();
			foreach ($errors as $key => $value)
			{
		//		$key = str_replace($base . '[', '', $key);
		//		$key = substr_replace($key, '', -1, 1);
				$this->errors[$key] = $value;
			}
			$this->validated = TRUE;
			$this->valid = FALSE;
			return FALSE;
		} else {
			$this->valid = TRUE;
			$this->validated = TRUE;
			return TRUE;
		}
	}

	/**
	 * Nagilum::getChildren()
	 *
	 * @description - this will get the immediate children of this model
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function getChildren()
	{
		// this will instantiate all uninstantiated child objects of the current class and pass $useAuto as TRUE

		// foreach has one relationship call getHasOne for it
		foreach ($this->hasOne as $key => $value)
		{
			$this->getHasOne($key, TRUE);
		}

		// foreach has many relationship call getHasMany for it
		foreach ($this->hasMany as $key => $value)
		{
			$this->getHasMany($key, TRUE);
		}

		return $this;
	}

	/**
	 * Nagilum::getChildrenAll()
	 *
	 * @description - This will retrieve the children of this object recursively
	 * @param bool $useAuto - whether to use the chilren's autoPopulateHasOne and autoPopulateHasMany values or to force instantiation of their children
	 * @return Nagilum $this - the current object for method chaining
	 */
	public function getChildrenAll($useAuto = FALSE)
	{
		// after each object has been instantiated we need to call getChildrenAll on it if $useAuto = FALSE
		if (!$useAuto || $this->autoPopulateHasOne)
		{
			// foreach has one relationship call getHasOne for it
			foreach ($this->hasOne as $key => $value)
			{
				if ($value != NULL)
				{
					$this->getHasOne($key, $useAuto);
				}
			}
		}

		if (!$useAuto || $this->autoPopulateHasMany)
		{
			// foreach has many relationship call getHasMany for it
			foreach ($this->hasMany as $key => $value)
			{
				if ($value != NULL)
				{
					$this->getHasMany($key, $useAuto);
				}
			}
		}

		return $this;
	}

	/**
	 * Nagilum::get()
	 *
	 * @description - Handles the shared components of the row and result methods
	 * @return CI_DB_Result $query - the resultant query from the AR method
	 */
	protected function get()
	{
		// if there's no order by set then use the default that's set
		$this->handleDefaultOrderBy();

		// call the pre Result Hooks
		$this->callPreResultHooks();

		// finds a multiple result object
		$this->resetObject();

		$this->db->from($this->table);

		//for filtered columns
		if(!empty($this->resultFilters))
		{
			foreach($this->filters as $column => $value)
			{
				$this->db->where($column, $value);
			}
		}

		$query = $this->db->get();

		// store the last run query and log it if needed
		$this->logQuery();

		return $query;
	}

	/**
	 * Nagilum::insert()
	 * @description - inserts a new record into the database
	 * @return bool $result - whether the insert was successful or not
	 */
	protected function insert()
	{
		// set the fields that need to be handled by the model
		$this->setFields();

		// add the created at and created by fields if they exist
		$this->createdAt();
		$this->createdBy();
		$this->updatedAt();
		$this->updatedBy();

		// actually save the data
		$result = $this->db->insert($this->table);

		// set the last insertId to the result
		$this->resultInsertId = $this->db->insert_id();
		// set the primary key of this object to the inserted id
		$this->data[$this->primaryKey] = $this->resultInsertId;

		$this->saveSuccess = $result;

		$this->logQuery();
		return $result;
	}

	/**
	 * Nagilum::update()
	 *
	 * @description - updates an existing record
	 * @return bool $success - whether the update was successful or not
	 */
	protected function update()
	{
		// set the fields that need to be handled by the model
		$this->setFields();

		// add the updated at and updated by fields if they exist
		$this->updatedAt();
		$this->updatedBy();

		// actually save the data
		$this->db->where($this->primaryKey, $this->data[$this->primaryKey]);
		$result = $this->db->update($this->table);

		// set the last insertId to the result
		$this->resultAffectedRows = $this->db->affected_rows();
		$this->saveSuccess = $result;

		$this->logQuery();
		return $result;
	}

	/**
	 * Nagilum::updateTimestamp()
	 *
	 * @description - Updates an objects updatedAt timestamp
	 * @return bool $result - whether the update was successful or not
	 */
	public function updateTimestamp()
	{
		$this->db->_reset_select();
		$this->updatedAt();
		$this->db->where($this->primaryKey, $this->data[$this->primaryKey]);
		$result = $this->db->update($this->table);

		$this->resultAffectedRows = $this->db->affected_rows();
		$this->saveSuccess = $result;

		$this->logQuery();

		return $result;
	}

	/**
	 * Nagilum::getValidationErrors()
	 *
	 * @description - returns the validation errors of this object as an array
	 * @return array $errors - the errors from validation of this object
	 */
	public function getValidationErrors()
	{
		return $this->errors;
	}

	/**
	 * Nagilum::getAjaxValidationErrors()
	 *
	 * @description - returns the validation errors of this object as an array that can be directly passed into the ajax class
	 * @return array $errors - an array of AjaxValidation objects
	 */
	public function getAjaxValidationErrors()
	{
		$errors = array();
		foreach ($this->errors as $key => $error)
		{
			if (array_key_exists($key, $this->stored))
			{
				$previous = $this->stored[$key];
			} else {
				$previous = NULL;
			}
			$obj = new AjaxValidation($key, $error, $previous);
			$errors[] = $obj;
		}

		return $errors;
	}

	/**
	 * Nagilum::isValid()
	 *
	 * @description - allows you to see if an object is valid or not
	 * @return bool $valid - TRUE if the object is valid FALSE otherwise
	 */
	public function isValid()
	{
		if (!$this->validated)
		{
			return FALSE;
		}

		return $this->valid;
	}

	/**
	 * Nagilum::isValidated()
	 *
	 * @description - allows you to check whether an object has been validated or not
	 * @return bool $validated - TRUE if the object has been validated FALSE otherwise
	 */
	public function isValidated()
	{
		return $this->validated;
	}

	/**
	 * Nagilum::saveRelationship()
	 *
	 * @description - Allows saving of an object to this model
	 * @param Nagilum $child - the object to save a relationship to
	 * @return void
	 */
	public function saveRelationship(Nagilum $child)
	{
		// overwrites existing relationship if it's a hasOne otherwise creates a new relationship
		// saves a relationship by passing in the object identifier somehow
		$name = $child->model;

		// determine if the relationships is a hasOne or a hasMany
		if (isset($this->hasOne[$name]))
		{

			$relationship = $this->hasOne[$name];
			$class = $name;
	    	$relation = $name; // the key we're going to store the relationship as

			// relationship overridden?
	    	if (isset($relationship['class']))
	    	{
	    		$class = $relationship['class'];
	    	}

			// create an instance of the child class so we can determine its details
			$child->save();
			$temp =& $child;
	    	$childTable = $temp->getTableName();
	    	$childPK = $temp->getPrimaryKey();

	    	// by default we assume we're not using a join table but are using this models table since we're in a has one relationship
	    	$joinTable = FALSE;
	    	$table = $this->table;

			// joinTable overridden?
	    	if (isset($relationship['joinTable']))
	    	{
	    		$table = $relationship['joinTable'];
	    		if ($table != $this->table)
	    		{
	    			$joinTable = TRUE;
	    		}
	    	}

	    	// by default we assume that the joinField is the relationship name followed by _id
	    	$joinField = $name . '_id';

			// joinField overridden?
	    	if (isset($relationship['joinField']))
	    	{
	    		$joinField = $relationship['joinField'];
	    	}


	    	// by default we assume that the childsJoinField is it's primary key
			$childJoinField = $childPK;

			// childJoinField overridden?
			if (isset($relationship['childJoinField']))
			{
				$childJoinField = $relationship['childJoinField'];
			}

			if (!$joinTable)
			{
				$query = $this->db->where($this->primaryKey, $this->data[$this->primaryKey]);
				$query = $this->db->set($joinField, $temp->$childPK);
				$query = $this->db->update($table);
			} else {
				$query = $this->db->from($table);
				$query = $this->db->where($joinField, $this->data[$this->primaryKey]);
				$query = $this->db->get();

				if ($query->num_rows() == 0)
				{
					$this->db->set($joinField, $this->data[$this->primaryKey]);
					$this->db->set($childJoinField, $temp->$childPK);
					$this->db->insert($table);
				} else {
					$this->db->set($childJoinField, $temp->$childPK);
					$this->db->where($joinField, $this->data[$this->primaryKey]);
					$this->db->update($table);
				}
			}

			return;
		}

		if (isset($this->hasMany[$name]))
		{
			$relationship = $this->hasMany[$name];

	    	// by default we assume the class name is the same as the relationship name
	    	$class = $name;
	    	$relation = $name; // the key we're going to store the relationship as

			// relationship overridden?
	    	if (isset($relationship['class']))
	    	{
	    		$class = $relationship['class'];
	    	}

			// create an instance of the child class so we can determine its details
			$child->save();
	    	$temp =& $child;

	    	// by default we assume we're not using a join table but are using this child models table since we're in a has many relationship
	    	$joinTable = FALSE;
	    	$table = $temp->getTableName();
	    	$childTable = $table; // in case there is a join table
	    	$childPK = $child->getPrimaryKey();

			// joinTable overridden?
	    	if (isset($relationship['joinTable']))
	    	{
	    		$table = $relationship['joinTable'];
	    		if ($table != $temp->getTableName())
	    		{
	    			$joinTable = TRUE;
	    		}
	    	}

	    	// by default we assume that the joinField is the current class's model name followed by _id
	    	$joinField = $this->model . '_id';

			// joinField overridden?
	    	if (isset($relationship['joinField']))
	    	{
	    		$joinField = $relationship['joinField'];
	    	}

	    	// by default we assume that the childsJoinField is it's model name followed by _id
			$childJoinField = $temp->getModelName() . '_id';

			// childJoinField overridden?
			if (isset($relationship['childJoinField']))
			{
				$childJoinField = $relationship['childJoinField'];
			}

			if (!$joinTable)
			{
				$this->db->set($joinField, $this->data[$this->primaryKey]);
				$this->db->where($childPK, $temp->$childPK);
				$this->db->update($table);
			} else {
				$query = $this->db->from($table);
				$query = $this->db->where($joinField, $this->primaryKey);
				$query = $this->db->where($childJoinField, $temp->$childPK);
				$query = $this->db->get();

				if ($query->num_rows == 0)
				{
					$query = $this->db->set($joinField, $this->primaryKey);
					$query = $this->db->set($childJoinField, $temp->$childPK);
					$query = $this->db->insert($table);
				}
			}

			return;
		}

		// no relationship found throw an exception
		throw new Exception('No relationship has been defined for this object');
	}

	/**
	 * Nagilum::deleteRelationship()
	 *
	 * @description - Allows deleting of a relationship from this model
	 * @param Nagilum $child - the object to delete the relationship to
	 * @return void
	 */
	public function deleteRelationship(Nagilum $child)
	{
		// deletes a relationship by passing in the object identifier somehow
		$name = $child->model;

		// determine if the relationships is a hasOne or a hasMany
		if (isset($this->hasOne[$name]))
		{

			$relationship = $this->hasOne[$name];
			$class = $name;
	    	$relation = $name; // the key we're going to store the relationship as

			// relationship overridden?
	    	if (isset($relationship['class']))
	    	{
	    		$class = $relationship['class'];
	    	}

			// create an instance of the child class so we can determine its details
	    	$childTable = $child->getTableName();
	    	$childPK = $child->getPrimaryKey();

	    	// by default we assume we're not using a join table but are using this models table since we're in a has one relationship
	    	$joinTable = FALSE;
	    	$table = $this->table;

			// joinTable overridden?
	    	if (isset($relationship['joinTable']))
	    	{
	    		$table = $relationship['joinTable'];
	    		if ($table != $this->table)
	    		{
	    			$joinTable = TRUE;
	    		}
	    	}

	    	// by default we assume that the joinField is the relationship name followed by _id
	    	$joinField = $name . '_id';

			// joinField overridden?
	    	if (isset($relationship['joinField']))
	    	{
	    		$joinField = $relationship['joinField'];
	    	}


	    	// by default we assume that the childsJoinField is it's primary key
			$childJoinField = $childPK;

			// childJoinField overridden?
			if (isset($relationship['childJoinField']))
			{
				$childJoinField = $relationship['childJoinField'];
			}

			if(isset($relationship['whereClause']))
			{
				$whereClause = $relationship['whereClause'];
				$whereBool = TRUE;
				if($whereClause['clause'] == 'NULL')
				{
					$whereBool = FALSE;
				}
				$this->db->where($whereClause['field'], $whereClause['clause'], $whereBool);
			}

			if (!$joinTable)
			{
				$query = $this->db->where($this->primaryKey, $this->data[$this->primaryKey]);
				$query = $this->db->set($joinField, NULL);
				$query = $this->db->update($table);
			} else {
				$query = $this->db->from($table);
				$query = $this->db->where($joinField, $this->data[$this->primaryKey]);
				$query = $this->db->get();

				if ($query->num_rows() > 0)
				{
					$this->db->where($joinField, $this->data[$this->primaryKey]);
					$this->db->delete($table);
				}
			}

			return;
		}

		if (isset($this->hasMany[$name]))
		{
			$relationship = $this->hasMany[$name];

	    	// by default we assume the class name is the same as the relationship name
	    	$class = $name;
	    	$relation = $name; // the key we're going to store the relationship as

			// relationship overridden?
	    	if (isset($relationship['class']))
	    	{
	    		$class = $relationship['class'];
	    	}

	    	// by default we assume we're not using a join table but are using this child models table since we're in a has many relationship
	    	$joinTable = FALSE;
	    	$table = $child->getTableName();
	    	$childTable = $table; // in case there is a join table
	    	$childPK = $child->getPrimaryKey();

			// joinTable overridden?
	    	if (isset($relationship['joinTable']))
	    	{
	    		$table = $relationship['joinTable'];
	    		if ($table != $child->getTableName())
	    		{
	    			$joinTable = TRUE;
	    		}
	    	}

	    	// by default we assume that the joinField is the current class's model name followed by _id
	    	$joinField = $this->model . '_id';

			// joinField overridden?
	    	if (isset($relationship['joinField']))
	    	{
	    		$joinField = $relationship['joinField'];
	    	}

	    	// by default we assume that the childsJoinField is it's model name followed by _id
			$childJoinField = $child->getModelName() . '_id';

			// childJoinField overridden?
			if (isset($relationship['childJoinField']))
			{
				$childJoinField = $relationship['childJoinField'];
			}

			if (!$joinTable)
			{
				$this->db->set($joinField, NULL);
				$this->db->where($childPK, $child->$childPK);
				$this->db->update($table);
			} else {
				$query = $this->db->from($table);
				$query = $this->db->where($joinField, $this->primaryKey);
				$query = $this->db->where($childJoinField, $child->$childPK);
				$query = $this->db->get();

				if ($query->num_rows() > 0)
				{
					$query = $this->db->where($joinField, $this->primaryKey);
					$query = $this->db->where($childJoinField, $child->$childPK);
					$query = $this->db->delete($table);
				}
			}

			return;
		}

		// no relationship found throw an exception
		throw new Exception('No relationship has been defined for this object');
	}

	/**
	 * Nagilum::createdAt()
	 *
	 * @description - Handles the automatic created at field
	 * @return void
	 */
	protected function createdAt()
	{
		if (!$this->fieldExists($this->createdAtField))
		{
			return;
		}
		$created_at = $this->createdAtField;
		if($this->$created_at == NULL)
		{
			if ($this->useOldStyleAutoFields)
			{
				$this->$created_at = time();
			} else {
				$this->$created_at = date('Y-m-d H:i:s');
			}
		}

		$this->db->set($created_at, $this->$created_at);
	}

	/**
	 * Nagilum::createdBy()
	 *
	 * @description - Handles the automatic created by field
	 * @return void
	 */
	protected function createdBy()
	{
		if (!$this->fieldExists($this->createdByField))
		{
			return;
		}
		$created_by = $this->createdByField;
		if($this->$created_by == NULL)
		{
			$this->$created_by = $this->CI->nUserId;
		}

		$this->db->set($created_by, $this->$created_by);
	}

	/**
	 * Nagilum::updatedAt()
	 *
	 * @description - Handles the automatic updated at field
	 * @return void
	 */
	protected function updatedAt()
	{
		if (!$this->fieldExists($this->updatedAtField))
		{
			return;
		}
		$updated_at = $this->updatedAtField;
		if ($this->useOldStyleAutoFields)
		{
			$this->$updated_at = time();
		} else {
			$this->$updated_at = date('Y-m-d H:i:s');
//			return;
		}

		$this->db->set($updated_at, $this->$updated_at);
	}

	/**
	 * Nagilum::updatedBy()
	 *
	 * @description - Handles the automatic updated at field
	 * @return void
	 */
	protected function updatedBy()
	{
		if (!$this->fieldExists($this->updatedByField))
		{
			return;
		}
		$updated_by = $this->updatedByField;
		$this->$updated_by = $this->CI->nUserId;

		$this->db->set($updated_by, $this->$updated_by);
	}

	/**
	 * Nagilum::buildFromResultArray()
	 *
	 * @description - builds a model from an array of data (for use with row and result)
	 * @param array $row - the data to build the model from
	 * @return void
	 */
	public function buildFromResultArray($row)
	{
		$this->data = $row;
		$this->stored = $row;
	}

	/**
	 * Nagilum::logQuery()
	 *
	 * @description - handles logging of the queries
	 * @return void
	 */
	protected function logQuery()
	{
		$this->lastRunQuery = $this->db->last_query();

		if ($this->logQueries)
		{
			log_message('debug', 'Class: ' . get_called_class() . ' - Query Run: ' . $this->lastRunQuery);
		}
	}

	/**
	 * Nagilum::lastQuery()
	 *
	 * @description - returns the last run query
	 * @return strin $query - the last run query
	 */
	public function lastQuery()
	{
		return $this->lastRunQuery;
	}

	/**
	 * Nagilum::builtQuery()
	 *
	 * @description - returns the built query
	 * @return strin $query - the built query
	 */
	public function builtQuery()
	{
		return $this->db->_compile_select();
	}

    // linh
    /**
     * Nagilum::getQuery()
     *
     * @description - returns the current built query
     * @return strin $query - the current built query
     */
    public function getQuery()
    {
        $clone_db = clone($this->db);

        // NOTE: from my understand nothing much happens here since it's rarely used
        // if there's no order by set then use the default that's set
        $this->handleDefaultOrderBy();

        // WARNNG: this can cause issues, needs to be tested
        // call the pre Result Hooks
        $this->callPreResultHooks();

        $clone_db->from($this->table);

        // NOTE: from my understand nothing much happens here since it's rarely used
        //for filtered columns
        if(!empty($this->resultFilters))
        {
            foreach($this->filters as $column => $value)
            {
                $clone_db->where($column, $value);
            }
        }

        return $clone_db->_compile_select();
    }

	/**
	 * Nagilum::getClone()
	 *
	 * @description - returns an exact copy of this object
	 * @return Nagilum $clone - the clone of the object
	 */
	public function getClone()
	{
		$temp = clone($this);

		$temp->db = clone($this->db);

		return $temp;
	}

	/**
	 * Nagilum::getCopy()
	 *
	 * @description - returns a clone of this object with a Null id
	 * @return Nagilum $copy - The copy of this object
	 */
	public function getCopy()
	{
		$copy = $this->getClone();

		$copy->id = NULL;
		$created_by = $this->createdByField;
		$created_at = $this->createdAtField;
		$copy->$created_by = NULL;
		$copy->$created_at = NULL;

		$updated_by = $this->updatedByField;
		$updated_at = $this->updatedAtField;
		$copy->$updated_by = NULL;
		$copy->$updated_at = NULL;

		return $copy;
	}

	public function switchDb($db)
	{
		$this->db = $db;
	}

	public function dateFormat($colName, $format = 'Y-m-d', $timezoneAdjust = FALSE)
	{
//		$setting = new setting();
//		$setting->where('user_id', $this->CI->nUserId);
//		$setting->where('name', 'datetimeformat');
//		$setting->result();
		
		if (!empty($this->$colName))
		{
			$date = new DateTime($this->$colName);
			if ($timezoneAdjust){
				$date->setTimezone(new DateTimeZone($this->CI->sTimeZone));
			}
			return $date->format($format);
		}
		return NULL;
	}

	/**
	 * Nagilum::getCachedTableData()
	 *
	 * @description - Returns cached table metaData
	 * @param mixed $table
	 * @return
	 */
	public static function getCachedTableData($table)
	{
		if (isset(Nagilum::$tableFieldCache[$table]))
		{
			return Nagilum::$tableFieldCache[$table];
		} else {
			return FALSE;
		}
	}

	/**
	 * Nagilum::clearCachedTableData()
	 *
	 * @description - removes table metaData in the cache
	 * @param mixed $table
	 * @return
	 */
	public static function clearCachedTableData($table = NULL)
	{
		if ($table)
		{
			unset(Nagilum::$tableFieldCache[$table]);
		} else {
			Nagilum::$tableFieldCache = array();
		}
	}

	/**
	 * Nagilum::setCachedTableData()
	 *
	 * @description - stores table metaData in the cache
	 * @param mixed $table
	 * @param mixed $data
	 * @return
	 */
	public static function setCachedTableData($table, $data)
	{
		Nagilum::$tableFieldCache[$table] = $data;
	}

    /**
     * Nagilum::autoload()
     *
     * @description - handles autoloading of the models
     * @param mixed $class
     * @return
     */
    public static function autoload($class)
    {
        $CI = EP_Controller::getInstance();

        // Don't attempt to autoload CI_ , EE_, or custom prefixed classes
		if (in_array(substr($class, 0, 3), array('CI_', 'EE_')) OR strpos($class, $CI->config->item('subclass_prefix')) === 0)
		{
			return;
		}

		// Prepare class
		$class = strtolower($class);

		// Prepare path
		if (isset($CI->load->_ci_model_paths) && is_array($CI->load->_ci_model_paths))
		{
			// use CI 2.0 loader's model paths
			$paths = $CI->load->_ci_model_paths;
		}
		else
		{
			// search only the applications models folder
			$paths[] = APPPATH;
		}

		foreach ($paths as $path)
		{
			// Prepare file
			$file = $path . 'models/' . $class . EXT;

			// Check if file exists, require_once if it does
			if (file_exists($file))
			{
				require_once($file);
				break;
			}
		}

		// if class not loaded, do a recursive search of model paths for the class
		if (! class_exists($class))
		{
			foreach ($paths as $path)
			{
				$found = self::recursive_require_once($class, $path . 'models');
				if ($found)
				{
					break;
				}
			}
		}
    }

    /**
     * Nagilum::recursive_require_once()
     *
     * @description - handles including models php files
     * @param mixed $class
     * @param mixed $path
     * @return
     */
    protected static function recursive_require_once($class, $path)
	{
		$found = FALSE;
		if (is_dir($path))
		{
			$handle = opendir($path);
			if ($handle)
			{
				while (($dir = readdir($handle)) !== FALSE)
				{
					// If dir does not contain a dot
					if (strpos($dir, '.') === FALSE)
					{
						// Prepare recursive path
						$recursive_path = $path . '/' . $dir;

						// Prepare file
						$file = $recursive_path . '/' . $class . EXT;

						// Check if file exists, require_once if it does
						if (file_exists($file))
						{
							require_once($file);
							$found = TRUE;

							break;
						}
						else if (is_dir($recursive_path))
						{
							// Do a recursive search of the path for the class
							self::recursive_require_once($class, $recursive_path);
						}
					}
				}

				closedir($handle);
			}
		}
		return $found;
	}

	// three methods to rule them all and in the darkness unset them
	public static function destroy(&$obj)
	{
		if(is_array($obj))
		{
			self::unsetArray($obj);
		}
		if(!is_object($obj) || !is_a($obj, 'Nagilum'))
		{
			unset($obj);
			return;
		}

		if($obj->cType == 'data')
		{
			// here we have a data object so we need to unset it's data array
			foreach($obj->data as &$item)
			{
				if(is_array($item))
				{
					self::unsetArray($item);
				}
				unset($item);
			}
		}

		self::destroyChildren($obj);
		unset($obj);
	}

	public static function destroyChildren($obj)
	{
		foreach($obj->hasOne as $child)
		{
			self::destroy($child);
		}

		foreach($obj->hasMany as $child)
		{
			self::destroy($child);
		}

		$obj->hasOne = array();
		$obj->hasMany = array();
	}

	public static function unsetArray(&$arr)
	{
		foreach($arr as &$item)
		{
			if(is_array($item))
			{
				self::unsetArray($item);
			}
			if(is_object($item) && is_a($item, 'Nagilum'))
			{
				self::destroy($item);
			}
			unset($item);
		}
	}
}


/**
 * Autoload
 *
 * Autoloads object classes and models for nagilum
 */
spl_autoload_register('nagilum::autoload');

// EOF
