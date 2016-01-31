<?php
class DateTimeInput extends DateTime
{
	public $partialDate = FALSE;
	public $month = NULL;
	public $year = NULL;
	public $date;
	protected $dateTime = NULL;

	/*
	 * determine if the date passed in is just a month and year or just a year, otherwise set the date on parent object according to m-d-Y format
	 * Year is required to be 4 digits or an exception will be thrown.
	 * to use: instead of instantiating a new DateTime object do the following:
	 * 	$dateTime = new DateTimeInput;
	 * 	$dateTime->isPartialDate(ctrl::$in['date']);
	 *
	 * if the date is a partial date, the object will have $partialDate set to TRUE.
	 *
	 */
	public function isPartialDate($dateInput)
	{
		$dateInput = str_replace('&nbsp;', ' ', $dateInput);
		$dateInput = str_replace('  ', ' ', $dateInput);
		$dateInput = str_replace('<br>', '', $dateInput);
		//TODO use regex to split input and make sure only digits are being passed in
		$date = explode('/', str_replace(array('\'', '-', '.', ','), '/', $dateInput));
		$dateCount = count($date);
		$noslashes = FALSE;
		if($dateCount == 1 && strlen($dateInput) == 8)
		{
			$date = $date[0];
			$date = substr($date, 0, 2) . '/' . substr($date, 2, 2) . '/' . substr($date, 4);
			$date = explode('/', $date);
			$dateCount = count($date);
			$noslashes = TRUE;
		} elseif($dateCount == 1 && strlen($dateInput) == 6) {
			$date = $date[0];
			$date = substr($date, 0, 2) . '/' . substr($date, 2);
			$date = explode('/', $date);
			$dateCount = count($date);
		}
		if (!empty($date[2]) && $date[2] == '00 00:00:00')
		{
			$date = array($date[0], $date[1]);
			$dateCount = count($date);
		}
		if (!empty($date[1]) && $date[1] == '00')
		{
			$date = array($date[0]);
			$dateCount = count($date);
		}

		if($dateCount == 1 && $date && strlen($date[0]) == 4)
		{
			$this->year = intval($date[0]);
			$this->partialDate = TRUE;
			$this->setDate(intval($date[0]), 1, 1);
			return TRUE;
		}

		if($dateCount == 1 && $date && strlen($date[0]) == 2)
		{
			$this->year = intval($date[0]);
			$this->partialDate = TRUE;
			$this->setDate(intval($date[0]), 1, 1);
			return TRUE;
		}

		if($dateCount == 1 && $date && strlen($date[0]) == 1)
		{
			$this->year = '200'.intval($date[0]);
			$this->partialDate = TRUE;
			$this->setDate('200'.intval($date[0]), 1, 1);
			return TRUE;
		}

		if($dateCount == 2) {
			if(strlen($date[0]) == 4) {
				$this->year = $date[0];
				$this->month = $date[1];
			} elseif (strlen($date[0]) < 3 && intval($date[0]) < 13 ) {
				$this->year = $date[1];
				$this->month = $date[0];
			}

			if(!$this->year || !$this->month) {
				throw new Exception('Invalid date');
				return FALSE;
			}

			$this->partialDate = TRUE;
			$this->setDate($this->year, $this->month, 1);
		}

		if(!$this->partialDate)
		{
			if($noslashes)
			{
				$this->dateTime = new DateTIme();
				$this->dateTime->setDate($date[2], $date[0], $date[1]);
			} else {

				if (strlen($dateInput) == 1)
				{
					$this->dateTime = new DateTIme();
					return $this;
				}

				$testDate = strtotime($dateInput);
				if ($testDate === FALSE)
				{
					throw new Exception('Invalid date provided: '.$dateInput);
					return FALSE;
				}
				$this->dateTime = new DateTime($dateInput);
			}
	//		$this->__construct($dateInput);
	//		$this->createFromFormat('m#d#Y', $dateInput);
		}

		return $this->partialDate;
	}

	/*
	 * to be used instead of DateTime->format('Y-m-d H:i:s') for inserting or updating SQL records.
	 * If the object has $partialDate set to TRUE, it will use 0s so that we aren't inserting bad data into DATETIME,
	 * otherwise it will format the date based on Y-m-d H:i:s
	 */
	public function format($type)
	{
		if ($type == 'sql')
		{
			if($this->partialDate && $this->month && $this->year) {
				return $this->year.'-'.$this->month.'-00 00:00:00';
			}

			if($this->partialDate && $this->year && !$this->month) {
				return $this->year.'-00-00 00:00:00';
			}

			if ($this->dateTime == NULL)
				$this->dateTime = new DateTime();

			return $this->dateTime->format('Y-m-d H:i:s');

		} else {
			if ($this->dateTime !== NULL)
			{
				return $this->dateTime->format($type);

			} else {
				$parentClass = get_parent_class($this);
				$parent = new $parentClass($this->date);
				if ($this->partialDate)
				{
					$parent->setDate($this->year, $this->month, 1);
				}
				return $parent->format($type);
			}
		}
	}
}
//EOF
