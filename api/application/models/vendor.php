<?php
/*
 * vendor.php
 * Brian Markham 03/04/2016
 *
*/
class vendor extends Nagilum {

	public $table = 'vendor';
	
	public $hasOne = array(	//'transactions' => array('class' => 'transaction', 'joinField' => 'transaction_id'),
							//'category' => array('class' => 'category', 'joinField' => 'category_id'),
							//'bank_account' => array('class' => 'bank_account', 'joinField' => 'bank_account_id')
						);
	public $hasMany = array(//'repeats' => array('class' => 'transaction_repeat_every', 'joinField' => 'transaction_repeat_id'),
							//'splits' => array('class' => 'transaction_repeat_split', 'joinField' => 'transaction_repeat_id')
						);

	public $autoPopulateHasOne = FALSE;
	public $autoPopulateHasMany = FALSE;

	public function __construct($id = FALSE) {
		parent::__construct($id);
	}

	public function postResultHook() {
		parent::postResultHook();

		$displayName = array($this->name);
		if ($this->street) {
			$displayName[] = $this->street;
		}
		if ($this->city) {
			$displayName[] = ($this->state) ? $this->city . ',': $this->city;
		}
		if ($this->state) {
			$displayName[] = $this->state;
		}
		$this->display_name = implode(' ', $displayName);
	}

}
//EOF