<?php

class EP_Session
{
	const DEBUG = FALSE;						// set to TRUE to enable session logging

	private static $oDb = null;					// database instance to use for session read/writes
	private static $fIgnoreTTLReset = FALSE;	// TRUE when session writes do not update TTL
	private static $sSessId = NULL;				// session id; used when extending the session time
	public $db = null;

	//const SESSION_TTL = 43200;				// 12 hrs
	//const SESSION_TTL = 286400;				// 24 hrs
	//const SESSION_TTL = 900;					// 15 minute timeout
	const SESSION_TTL = 3600;					// 1 hour
	const SESSION_NAME = 'ptpbio';			// the session name
	const TABLE = 'user_session';			// table name used to store the session

	// define constants used for accessing session data
	const DATA_ID = 'id';
	const DATA_NAME = 'uname';
	const DATA_FULLNAME = 'fullName';
	const DATA_EXPERT = 'expert';
	const DATA_ROLE = 'role';
	const DATA_ADMIN = 'admin';
	const DATA_PROV = 'prov';
	const DATA_PATIENT = 'patient';				// current patient
	const DATA_PATIENT_LIST = 'patientlist';	// list of patient tabs

	private static $sess_loggedin = FALSE;	// TRUE when user is logged in

	// used for managing the list of recently accesses patients
	const MAX_PATIENT_LIST = 10;			// TODO: make configurable
	const MAX_PATIENT_TAB = 5;				// TODO: make configurable

	// used in handling the recently edited patient stack
	const ARR_ID = 'id';					// patient id
	const ARR_NAME = 'name';				// patient name
	const ARR_TAB = 'tab';					// last tab used for this patient
	const ARR_PM = 'pm_tab';				// last practice management tab used for this patient


	// cookie names
	const EXP_COL_STATE = 'pi_expColState';	// cookie for expand/collapse patient info header

	public static function init($db, $fIgnoreTTL = FALSE)
	{
		register_shutdown_function('session_write_close');
//self::debug('EP_Session::init()');

		// set session values
		ini_set('session.gc_probability', 1);
		ini_set('session.gc_divisor', 1000);
		ini_set('session.gc_maxlifetime', self::SESSION_TTL);

		self::$oDb = $db;
		self::$fIgnoreTTLReset = $fIgnoreTTL;

		session_name(self::SESSION_NAME);

		session_set_save_handler(array('EP_Session', 'open'),
								 array('EP_Session', 'close'),
								 array('EP_Session', 'read'),
								 array('EP_Session', 'write'),
								 array('EP_Session', 'destroy'),
								 array('EP_Session', 'gc'));
//phpinfo();
		if(!isset($_SESSION))
		{
			$res = session_start();
		}
		//if ($res === FALSE)

		if (!isset($_SESSION[self::DATA_ID]))
			$_SESSION[self::DATA_ID] = 0;
		if (!isset($_SESSION[self::DATA_NAME]))
			$_SESSION[self::DATA_NAME] = '';
		if (!isset($_SESSION[self::DATA_FULLNAME]))
			$_SESSION[self::DATA_FULLNAME] = '';
		if (!isset($_SESSION[self::DATA_EXPERT]))
			$_SESSION[self::DATA_EXPERT] = '';

		if ($_SESSION[self::DATA_ID] > 0 && !empty($_SESSION[self::DATA_NAME]))
		{
			self::$sess_loggedin = TRUE;
		}
//self::debug('EP_Session::init() end');
	}

	// sets the db. used when inheriting sessions via Ghost Login
	public static function setDb($db)
	{
		self::$oDb = $db;
	}

	// opens session
	public static function open($sSavePath, $sSessName)
	{
		$db = self::$oDb;

		self::$sSessId = $sSessId = session_id();
		$aData = array();
		$aData['id'] = $sSessId;
//self::debug('EP_Session::open(' . $sSavePath . ', ' . $sSessName . ') id=' . $sSessId);

//		db::_replace(self::TABLE, $aData, ' where id=' . db::_quote($sSessId));

		$query = $db->from(self::TABLE);
		$query = $db->where('id', $sSessId);
		$query = $db->get();

		$result = $query->row();

//		$res = db::_qRow('select id from ' . self::TABLE . ' where id=' . db::_quote($sSessId));
		if ($query->num_rows() < 1 || $result->id != $sSessId)
		{
			// session doesn't exist, create an empty record
			$aData['id'] = $sSessId;
			$aData['time'] = time() + self::SESSION_TTL;

			$query = $db->insert(self::TABLE, $aData);
		}

//		else
//			$res = db::_up(self::TABLE, $aData, array('id' => $sSessId));

//self::debug(' db result=[' . print_r($result, TRUE) . ']');
		return (TRUE);
	}

	// closes session
	public static function close()
	{
//self::debug('EP_Session::close()');
		return (TRUE);
	}

	// reads session information from database table
	public static function read($sSessId)
	{
//self::debug('EP_Session::read(' . $sSessId . ')');
		$db = self::$oDb;

		$query = $db->from(self::TABLE);
		$query = $db->where('id', $sSessId);
		$query = $db->get();

		$num_rows = $query->num_rows();

		$result = $query->row();

//		$sql = 'select time, data from ' . self::TABLE . '
//				where id=' . db::_quote($sSessId);
//		$result = db::_qRow($sql);

		if ($num_rows > 0)
		{
			// if current time greater than session expire time
			// and it's not an ajax request
			//  -- then kill the data
			if (time() > intval($result->time)) //  && !self::$fIsAjax)
			{
//self::debug('EP_Session::read() - expired, clearing data');
				$result->data = '';
			}

			return ($result->data);
		}
//else self::debug('EP_Session::read() - session id not found');
		return ('');
	}

	// stores the session data
	public static function loginUser($sSessId, $sData)
	{
		$db = self::$oDb;
		$aData = array();
		$aData['data'] = $sData;
		$query = $db->where('id', $sSessId);
		$query = $db->update(self::TABLE, $aData);
		return true;
	}

	public static function write($sSessId, $sData, $fIn = FALSE)
	{
//self::debug('');
//log_message("debug", "EP Session Write Fired");
		$db = self::$oDb;

		$aData = array();
		$aData['data'] = $sData;
		if (!self::$fIgnoreTTLReset)						// if it's not an Ajax GET request
		{
			$aData['time'] = time() + self::SESSION_TTL;	// update the expire time
		}

		if ($fIn)
		{
			$aData['id'] = $sSessId;
			$aData['time'] = time() + 6;					// sessions created via Ghost Login live for 6 seconds
			$db->replace(self::TABLE, $aData);
//self::debug('EP_Session::write():  inserting data (' . $sSessId . ') with [' . $sData . ']');
		} else {
			$db->_reset_write();
			$query = $db->where('id', $sSessId);
			$query = $db->update(self::TABLE, $aData);
//self::debug('EP_Session::write():  updating data (' . $sSessId . ') with [' . $sData . ']');
		}

		return (TRUE);
	}
	
	public static function hasSession() {
		return (self::$sess_loggedin);
	}	

	// extend a session's time to live
	public static function extendSession()
	{
		if (self::$sSessId != NULL)
		{
//self::debug('EP_Session::extendSession(): extending session ' . self::$sSessId . ' until ' . (time() + self::SESSION_TTL));
			$db = self::$oDb;

			$aData = array('time' => time() + self::SESSION_TTL);

			$query = $db->where('id', self::$sSessId);
			$query = $db->update(self::TABLE, $aData);
		}
	}

	// inherits a session being passed off by the Ghost Login
	public static function inheritSession($sAdminSessId)
	{
die;
//self::debug('EP_Session::inheritSession(' . $sAdminSessId . ')');
		$sNewSessId = session_id();
		$db = self::$oDb;

		$adminSession = $db->from(self::TABLE);
		$adminSession = $db->where('id', $sAdminSessId);
		$adminSession = $db->get();

		if ($adminSession->num_rows() > 0)
		{
			$arr = $adminSession->result_array();
			$sessionData = $arr[0]['data'];
			$dbArray = explode(';', $sessionData);
			foreach($dbArray as $key => $element)
			{
				if(!(empty($element)))
					{
						$field = substr($element, 0, strpos($element, '|'));
						$reverse = strrev($element);
						$reverseValue = substr($reverse, 0, strpos($reverse, ':'));
						$value = str_replace('"', '', strrev($reverseValue));
						$_SESSION[$field] = $value;
					}
			}

			$query = $db->where('id', $sNewSessId);
			$query = $db->delete(self::TABLE);

			$query = $db->where('id', $sAdminSessId);
			$query = $db->update(self::TABLE, array('id' => $sNewSessId, 'time' => time() + self::SESSION_TTL));
			self::$sess_loggedin = TRUE;
		}
	}

	// destroy the session
	public static function destroy($sSessId)
	{
//self::debug('EP_Session::destroy(' . $sSessId . ')');
		$db = self::$oDb;

		$query = $db->where('id', $sSessId);
		$query = $db->delete(self::TABLE);

//		$res = db::_q('delete from ' . self::TABLE . ' where id=' . db::_quote($sSessId));

//		session_destroy();
		setcookie(self::SESSION_NAME, '', -1, '/');
		return (TRUE);
	}

	public static function gc($nLifeTime)
	{
//self::debug('EP_Session::gc(' . $nLifeTime . ')');
		$db = self::$oDb;
//self::debug('  deleting sessions older than ' . time());

		$query = $db->where('time <', time());
		$query = $db->delete(self::TABLE);
		//db::_q('delete from ' . self::TABLE . ' WHERE time <' . time());
	}

	// used for debug logging
	private static function debug($sMsg)
	{
		if (self::DEBUG)
		{
			$sFile = APPPATH . 'logs/sessionlog.txt';
			$fh = fopen($sFile, 'a+');
			if ($fh !== FALSE)
			{
				fwrite($fh, date('m-d-Y H:i:s ') . $sMsg . "\r\n");
				fclose($fh);
			}
		}
	}
}

// EOF
