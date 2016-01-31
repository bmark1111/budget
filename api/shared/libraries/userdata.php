<?php

/**
 * @module UserData
 * @author - Brian Markham
 * @version 0.01
 * @access public
 */
class UserData {

	private $_userData_JSON = FALSE;

	/**
	 * @constructor
	 */
	public function __construct() {
		$this->CI =& EP_Controller::getInstance();
	}

	/**
	 * Get User Data
	 * @method get
	 * @param {string} $key valid key contained in user data json string
	 */
	public function get($key) {
		if (!$this->_userData_JSON) {
			$this->_buildUserJSON();
		}
		return (!empty($this->_userData_JSON[$key])) ? $this->_userData_JSON[$key]: FALSE;
	}

	/**
	 * 
	 */
	public function put($key, $value) {
		if (!$this->_userData_JSON) {
			$this->_buildUserJSON();
		}

		$this->_userData_JSON[$key] = $value;

		$user_session = new user_session();
		$user_session->where('user_id', $this->CI->nUserId);
		$user_session->orderBy('expire', 'DESC');
		$user_session->row();

		$sql = "UPDATE user_session SET data = '" . json_encode($this->_userData_JSON) . "' WHERE id = '" . $user_session->id . "'";
		$user_session->query($sql);
	}

	/**
	 * 
	 */
	private function _buildUserJSON() {
		if ($this->nUserData) {
			$this->_userData_JSON = json_decode($this->nUserData);
		}
	}

}

// EOF