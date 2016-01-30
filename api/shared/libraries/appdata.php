<?php

/**
 * @module AppData
 * @author - Brian Markham
 * @version 0.01
 * @access public
 */
class AppData {

	/**
	 * @constructor
	 */
	public function __construct() {
//		$this->CI =& EP_Controller::getInstance();
	}

	/**
	 * Get User Data
	 * @method get
	 * @param {string} $key valid key
	 */
	public function get($key) {
		$app_data = new app_data();
		$app_data->where('`key`', $key);
		$app_data->row();
		return json_decode($app_data->data, TRUE);
	}

	/**
	 * 
	 */
	public function set($key, $value) {
		$app_data = new app_data();
		$app_data->where('`key`', $key);
		$app_data->row();
		if ($app_data->numRows()) {
			$sql = "UPDATE app_data SET `data` = '" . json_encode($value) . "' WHERE `key` = '" . $key . "'";
		} else {
			$sql = "INSERT INTO app_data (`key`,`data`) VALUES('" . $key . "', '" . json_encode($value) . "')";
		}
//echo "\n".$sql;
		$app_data->queryAll($sql);
//echo $app_data->lastQuery();
//die;
	}

	/**
	 * 
	 */
	public function remove($key) {
		$app_data = new app_data();
		$sql = "DELETE FROM app_data WHERE `key` = '" . $key . "'";
		$app_data->queryAll($sql);
	}

}

// EOF