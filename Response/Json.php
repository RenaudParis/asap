<?php
/**
 * @package Asap/Response
 */

/**
 * JSON response for ajax requests for example
 *
 * @package Asap/Response
 * @author Lideln
 */
class Asap_Response_Json extends Asap_Response_Generic
{
	public function __construct($aParams = array())
	{
		self::modelsToArrays($aParams);
		parent::__construct('json', '', $aParams);
	}

	/**
	 * Convert the possible Model objects to arrays for JSON answer
	 *
	 * @param array $aArr
	 */
	protected static function modelsToArrays(&$aArr)
	{
		foreach ($aArr as $sKey => &$mVal)
		{
			if (is_array($mVal))
				self::modelsToArrays($mVal);
			else if (is_object($mVal) && $mVal instanceof Asap_Database_Model)
				$aArr[$sKey] = $mVal->getData();
		}
	}
}
