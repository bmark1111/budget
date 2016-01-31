<?php
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * EP_Controller
 *
 * @package Core
 * @author Joshwa Fugett
 * @version 0.17
 * @access public
 *
 * @description This class is the core controller that houses all of the functionality needed by every (most) controllers in all of our applications
 *	  This controller may be extended further but in doing so you have to be careful that the extending class is included preferrably by adding it
 *	  in through a library or similar method of loading that sticks to the standards instead of using hand built file paths. Please be aware of what
 *	  this controller is doing fully before using as it is shared among all applications
 */
class EP_Controller extends MX_Controller
{
	private static $_instance = NULL; // instance to current EP_Controller

	private $sEnvironment = 'production'; // holds the environment variable, defaults to production in case we forget to set the variable
	private $bDbLoaded = FALSE; // this is used to determine if the dbutil class has been loaded which we must connect to budget_master first for this to be true
	private $aDbConfig = FALSE; // this holds the database config so that we can save some overhead when we need to switch databases
	public $nAccount = NULL; // this is the current account that the REST is being run for
	private $sClientDb = NULL; // this is the current (or previously) connected client database
	private $sPrefix = NULL; // used to store the database prefix if it exists

	public  $nUserId = NULL; // used to store the session id for the user
	public  $sUserName = NULL; // used to store the username for the currently logged in user
	public  $sFullUserName = NULL; // used to store the username for the currently logged in user
	public  $nExpertId = NULL; // used to store the implementor associated with this account
	public  $nStimulusExpertId = NULL; // used to store the implementor associated with this account
	public $title = ''; // used to store the title for the header

	public $aDBs = array(); // used to store the various databases connections that we load up when old connections die out??
	public $current_migration_db = NULL; // this is used only for specific migration requirements don't try this at home unless you know what you're doing

	public static $isModuleExtendSession = FALSE;

	private static $environment_flag = false;

	/**
	 * EP_Controller::__construct()
	 *
	 * @return - No Return Value
	 */
	public function __construct() {
		parent::__construct();

		// this is loaded at this point so we can use it to determine what REST we're accessing
		$this->load->helper('url');

		$httpReferer = explode('//', $_SERVER['HTTP_REFERER']);
		$httpReferer = explode('.', $httpReferer[1]);
		$httpReferer2 = array_reverse($httpReferer);
		if ($httpReferer2[1] == $this->config->item('referer')) {
			define('APPLICATION', 'REST');
		} else {
			throw new Exception('Invalid application requested');
		}
//		switch ($referer2[1]) {
//			case 'budgettracker':
//				define('APPLICATION', 'PUBLIC');
//				break;
//			case 'budget':
//				define('APPLICATION', 'REST');
//				break;
//			default:
//				throw new Exception('Invalid application requested');
//				break;
//		}

		// if the server environment variable has been set use it to override the environment
		if (isset($_SERVER['ENVIRONMENT'])) {
			$this->sEnvironment = $_SERVER['ENVIRONMENT'];
		}

		if (isset($_SERVER['PREFIX'])) {
			$this->sPrefix = $_SERVER['PREFIX'] . '_';
		}

		// switch to the master DB
		$this->switchDatabase('budget_master');

		// set the current instance of the object to this if it's not already set
		if(!isset(self::$_instance)) {
			self::$_instance =& $this;
		}

		// set up any module and library path chaining for specific applications
		// We're doing this by adding the directories to the *end* of the list.
		// The $this->load->_add_module_path() function adds to the beginning of
		// the list and we want the current environment's directory to take precedence.
		switch (APPLICATION) {
			case 'CLI':
				$this->load->_ci_library_paths[] = SHAREPATH;
				break;
		}

		// load any remaining libraries that are necessary
		$this->loadLibraries();

		$uri = explode('/', uri_string());
		if (APPLICATION == 'REST' && !empty($uri[0])) {
			switch($uri[0]) {
				case 'data':
				case 'upload':
					// check for ajax request
					if(!$this->input->is_ajax_request()) {
						$this->set_header("Not Found", '404');
						exit;
					}

					// this must be secure access - check auth, token, referer & remote_addr
					if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])
							&&
						!empty($_SERVER['HTTP_TOKENID']) && !empty($_SERVER['REMOTE_ADDR'])) {
//							&&
//						!empty($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] === 'http://budgettracker.loc/') {
						// query the database for the correct user & user session information
						$this->db->select('user_session.user_id, user_session.id as session_id, user_session.expire, user_session.account_id, account.db_suffix_name');
						$this->db->from('user_session');
						$this->db->join('account', 'account.id = user_session.account_id', 'left');
						$this->db->where('user_session.id', $_SERVER['HTTP_TOKENID']);
						$this->db->where('user_session.http_referrer', $_SERVER['HTTP_REFERER']);
						$this->db->where('user_session.ip_address', $_SERVER['REMOTE_ADDR']);
						$this->db->where('user_session.login', $_SERVER['PHP_AUTH_USER']);
						$this->db->where('user_session.pass', md5($_SERVER['PHP_AUTH_PW'] . $this->config->item('encryption_key')));
						$this->db->where('user_session.account_id', $_SERVER['HTTP_ACCOUNTID']);
						$oQuery = $this->db->get();

						// make sure that only one result is found
						if ($oQuery->num_rows() != 1) {
							$this->ajax->set_header("You are not authorzed", '401');
							exit;
						}
						$uSession = $oQuery->row();

						// check if session has expired
						if (time() > strtotime($uSession->expire)) {
							$this->ajax->set_header("EXPIRED", '401');
							exit;
						}

						// update the user_session 'expire' to 30 mins past now
						$data = array(
							'expire' => date('Y-m-d H:i:s', strtotime('+30 MINS'))
						);
						$this->db->where('id', $uSession->session_id);
						$this->db->update('user_session', $data); 

						// set the global account id
						$this->nAccount = $uSession->account_id;

						// set the global logged in user
						$this->nUserId = $uSession->user_id;

						// switch to the right account
						$this->switchDatabase('budget_'. $uSession->db_suffix_name);
					} else {
						$this->ajax->set_header("Not Found", '404');
						exit;
					}
					break;
				case 'register':
					// check for ajax request
					if(!$this->input->is_ajax_request()) {
						$this->set_header("Not Found", '404');
						exit;
					}
//					if (!empty($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] === 'http://budgettracker.loc/') {
						// lets try to register
//					} else {
//						$this->ajax->set_header("Not Found", '404');
//						exit;
//					}
					break;
				case 'login':
					// check for ajax request
					if(!$this->input->is_ajax_request()) {
						$this->set_header("Not Found", '404');
						exit;
					}
					if(!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
						$input = file_get_contents('php://input');
						$_POST = json_decode($input, TRUE);

						// check master DB for valid account
						$this->db->from('account');
						$this->db->where('account_num', $_POST['account']);
						$this->db->where('is_active', 1);
						$oQuery = $this->db->get();

						// make sure that only one result is found
						if ($oQuery->num_rows() != 1) {
							throw new Exception('Account Not Found');
						}
						// actually get the result
						$oRow = $oQuery->row();

						// set the global account id
						$this->nAccount = $oRow->id;

						// set the global db suffix
						$this->dbSuffix = $oRow->db_suffix_name;

						// switch to the user's account
						$this->switchDatabase('budget_'. $oRow->db_suffix_name);
					} else {
						$this->ajax->set_header("Not Found", '404');
						exit;
					}
					break;
				default:
					// check for ajax request
					if(!$this->input->is_ajax_request()) {
						$this->set_header("Not Found", '404');
					} else {
						$this->ajax->set_header("Not Found", '404');
					}
					exit;
					break;
			}
		} elseif (APPLICATION == 'CLI') {
			// ????????
		} else {
			if(!$this->input->is_ajax_request()) {
				$this->set_header("Not Found", '404');
			} else {
				$this->ajax->set_header("Not Found", '404');
			}
			exit;
		}
/*		elseif(APPLICATION == 'ADMIN')
		{
			// Used for the rss/atom newsfeed for Support News to bypass the ip whitelist:
			if ($this->uri->segment(1) == 'error' && $this->uri->segment(2) == 'newsfeed')
			{
				if(isset($this->session->userdata))
				{
					$this->nUserId = intval($this->session->userdata('user_id'));
				}
				return TRUE;
			}

			if(isset($this->session->userdata))
			{
				$this->nUserId = intval($this->session->userdata('user_id'));
			}
			$this->db->from('admin_user');
			$this->db->where('id', $this->nUserId);
			$query = $this->db->get();
			if ($query->num_rows() === 1 && !empty($query->row()->first_name))
			{
				$this->sUserName = $query->row()->username;
				$this->sFullUserName = $query->row()->first_name;
				if (!empty($query->row()->last_name))
				{
					$this->sFullUserName .= ' '.$query->row()->last_name;
				}
			}


			$ip_address = explode('.', $this->input->ip_address());

			$ip_whitelist = new ip_whitelist();
			$ip = '';
			for($i = 0; $i < count($ip_address); $i++)
			{
				if($i != 0)
				{
					$ip .= '.';
				}
				$ip .= $ip_address[$i];
				$ip_whitelist->orWhere('value', $ip);
			}
			$ip_whitelist->result();

			if($ip_whitelist->count() == 0)
			{
				log_message('error', 'User Failed IP Check with IP of ' . $this->input->ip_address());
				die('You don\'t have permission to access this application please contact the administrator.');
			}

			if(isset($this->session->userdata))
			{
				$this->nUserId = intval($this->session->userdata('user_id'));
			}
		}
*/
	}

	/**
	 * EP_Controller::switchDatabase()
	 *
	 * @description - allows us to switch databases or setup a new database connection from within our controllers
	 * @param string $db - this is the name of the database to connect to
	 * @param optional bool $return - this is whether you want the connection returned so you can have more than one connection open
	 * @return - returns the database if $return was TRUE
	 */
	public function switchDatabase($sDb, $oReturn = FALSE)
	{
		$this->sClientDb = $sDb;

		// see if the dbutil class is loaded, if so we can make sure a db exists prior to connecting
		if ($this->bDbLoaded && !$this->dbutil->database_exists($this->sPrefix . $sDb))
		{
			exit("<tt style=\"color: red; font-weight: bold\">The database couldn\'t be found.</tt>.");
		}

		// get the database configuration array
		if (!$this->aDbConfig)
		{
			$this->aDbConfig = $this->config->item('database');
	   }

		// override which database we're connecting to
		$this->aDbConfig['database'] = $this->sPrefix . $sDb;

		if(in_array($sDb, $this->aDBs))
		{
			$oDb = $this->aDBs[$sDb];
		} else {
			$oDb = $this->load->database($this->aDbConfig, TRUE, TRUE);
			$this->aDBs[$sDb] =& $oDb;
		}

		// if we're not returning set the CI db instance to the generated database
		if (!$oReturn)
		{
			CI::$APP->db = $oDb;
		}

		// if the database utility library hasn't been loaded before load it here since we now have a connection
		if(!$this->bDbLoaded)
		{
			$this->load->dbutil();
			$this->bDbLoaded = TRUE;
		}

		// return the requested db if needed
		if($oReturn)
		{
			return $oDb;
		}
	}

	/**
	 * EP_Controller::getEnvironment()
	 *
	 * @description - returns the given environment for the server
	 * @return string
	 */
	public function getEnvironment() {
		return $this->sEnvironment;
	}

	/**
	 * EP_Controller::loadLibraries()
	 *
	 * @description - loads any needed libraries
	 * @return - NULL
	 */
	private function loadLibraries() {
		if(APPLICATION == 'REST' || APPLICATION == 'PUBLIC') {
			$this->load->helper('usage');
		}
		$this->load->helper('inflector');

		$this->load->library(array('dataFormat', 'session', 'ajax', 'nagilum', 'userdata', 'appdata'));

		$this->form_validation->CI =& $this; // set the CI instance with form validation to the current controller

		// if the environment is not production enable the profiler
		if(ENVIRONMENT == 'production') {
			$this->output->enable_profiler(FALSE);
		} else {
			if(!$this->input->is_ajax_request()) {
				$this->output->enable_profiler($this->config->item('enable_profiler'));
			} else {
				$this->output->enable_profiler(FALSE);
			}
		}
	}

	/**
	 * EP_Controller::getInstance()
	 *
	 * @description - This is used to help setup the singleton design pattern
	 * @return - NULL
	 */
	public static function getInstance()
	{
		return (self::$_instance);
	}

	public static function isInstance($obj)
	{
		if($obj !== self::$_instance)
			return FALSE;
		return TRUE;
	}

	/**
	 * EP_Controller::_output()
	 *
	 * @description - This does some automatic replacement on the output to include the js and css
	 * @param - $output - the passed in output for the page
	 * @return - NULL
	 */
	public function _output($output, $printMode = FALSE)
	{
		$this->css->printMode = $printMode;
		$this->js->printMode = $printMode;

//		$output = str_replace('{*JS*}', $this->js->output(), $output);
//		$output = str_replace('{*CSS*}', $this->css->output(), $output);

		if($printMode)
		{
			return $output;
		}

		echo $output;
	}
}

//EOF