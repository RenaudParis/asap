<?php

namespace Asap\Util;


final class Asap_Util_Timer
{
	protected $_name;
	protected $_begin;
	protected $_end;


	public function __construct($sName)
	{
		$this->_name = $sName;
		$this->_begin = microtime(true);
	}

	public function stop($bDisplay = true)
	{
		$this->_end = microtime(true);
		$fDur = $this->_end - $this->_begin;
		if ($bDisplay)
			echo 'TIMER (' . $this->_name . ') : ' . $fDur . 's<br />';
		return $fDur;
	}
}
