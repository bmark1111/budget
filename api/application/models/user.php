<?php
/**
 * @name user
 * @author Brian Markham
 * @date 04/03/2015
 *
*/
class user extends Nagilum {

	public $table = 'user';

	public $hasOne = array(	'role' => array('class' => 'user_role', 'joinField' => 'user_role_id'),
							'session' => array('class' => 'user_session', 'joinField' => 'last_session_id')
						);

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = NULL) {
		parent::__construct($id);
	}

	//
	public function setSession(&$user) {
		$_SESSION['id']		= $user->id;
		$_SESSION['uname']	= $user->login;
		$_SESSION['role']	= $user->roles;

		return TRUE;
	}

	//
	public function login($sName, $sPass) {
		$password = md5($sPass . $this->CI->config->item('encryption_key'));
		$this->select('user.id, user.login, user.firstname, user.lastname, user_role.roles');
		$this->join('user_role', 'user_role.id = user.user_role_id');
		$this->where('active', 1);
		$this->where('login', $sName);
		$this->where('pass', $password);
		$user = $this->row();
		if ($user->numRows() && $this->setSession($user)) {
			return TRUE;
		}
		return FALSE;
	}

}

// EOF