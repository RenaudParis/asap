<?php


/**
 * Validator class
 *
 * @author Lideln
 */
class Asap_Util_Validator
{
	/**
	 * Check if it is an int (signed)
	 *
	 * @param mixed $mVal
	 */
	public static function isInt($mVal, $iMin = null, $iMax = null)
	{
		$bValid = (is_int($mVal) || ctype_digit($mVal));
		if (!$bValid)
			return false;
		$mVal = (int)$mVal;
		if ($iMin !== null && $mVal < $iMin)
			return false;
		if ($iMax !== null && $mVal > $iMax)
			return false;
		return true;
	}

	/**
	 * Check if it is an unsigned int
	 *
	 * @param mixed $mVal
	 * @param int $iMin = null
	 * @param int $iMax = null
	 */
	public static function isUint($mVal, $iMin = null, $iMax = null)
	{
		$bValid = (is_int($mVal) || ctype_digit($mVal));
		if (!$bValid)
			return false;
		$mVal = (int)$mVal;
		if ($iMin !== null && $iMin >= 0 && $mVal < $iMin)
			return false;
		if ($iMax !== null && $iMax >= 0 && $mVal > $iMax)
			return false;
		return true;
	}

	/**
	 * Check if it is a signed float
	 *
	 * @param mixed $mVal
	 */
	public static function isFloat($mVal, $fMin = null, $fMax = null)
	{
		$bValid = (is_float($mVal) || is_numeric($mVal));
		if (!$bValid)
			return false;
		$mVal = (float)$mVal;
		if ($fMin !== null && $mVal < $fMin)
			return false;
		if ($fMax !== null && $mVal > $fMax)
			return false;
		return true;
	}

	/**
	 * Check if it is an hexadecimal value
	 *
	 * @param mixed $mVal
	 */
	public static function isHexa($mVal)
	{
		return is_int($mVal) || ctype_xdigit($mVal);
	}

	/**
	 * Check if it is a boolean value
	 *
	 * @param mixed $mVal
	 */
	public static function isBool($mVal)
	{
		return filter_var($mVal, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null;
	}

	/**
	 * Check if it is an email
	 *
	 * @param mixed $mVal
	 */
	public static function isEmail($mVal)
	{
		return filter_var($mVal, FILTER_VALIDATE_EMAIL) !== false;
	}

	/**
	 * Check if it is an url
	 *
	 * @param mixed $mVal
	 */
	public static function isUrl($mVal)
	{
		return filter_var($mVal, FILTER_VALIDATE_URL) !== false;
	}

	/**
	 * Check if it is an IP address
	 *
	 * @param mixed $mVal
	 */
	public static function isIp($mVal)
	{
		return filter_var($mVal, FILTER_VALIDATE_IP) !== false;
	}

	/**
	 * Check if it is a valid international phone number
	 */
	public static function isInternationalPhoneNumber($mVal)
	{
		return preg_match('/^(00|\+)?[0-9]{2}[0-9]{4,16}$/', $mVal) ? true : false;
	}

	/**
	 * Check if it is a valid french phone number
	 */
	public static function isFrenchPhoneNumber($mVal)
	{
		return preg_match('/^0[1-9][0-9]{8}$/', $mVal) ? true : false;
	}

	public static function phoneFrenchToInter($mVal)
	{
		return '+33' . substr($mVal, 1);
	}

	public static function phoneFormatInter($mVal)
	{
		preg_match('/^(00|\+)?([0-9]{1,2})([0-9]{4,16})$/', $mVal, $aMatches);
		if (count($aMatches) != 4)
			return $mVal;
		return '+' . $aMatches[2] . $aMatches[3];
	}





	/**
	 * Check if it is a valid date format
	 *
	 * @param string $sDate
	 * @param string $sFormat
	 */
	public static function isDate($sDate, $sFormat = 'en')
	{
		$aMatches = array();
		switch ($sFormat)
		{
			case 'fr':
				$sRegexp = '/^([0-9]{2})-([0-9]{2})-([0-9]{4})$/';
				if (!preg_match($sRegexp, $sDate, $aMatches))
					return false;
				return checkdate($aMatches[2], $aMatches[1], $aMatches[3]);
			case 'en':
				$sRegexp = '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/';
				if (!preg_match($sRegexp, $sDate, $aMatches))
					return false;
				return checkdate($aMatches[2], $aMatches[3], $aMatches[1]);
			default:
				throw new Exception('Unknown date format');
		}
		return false;
	}

	/**
	 * Check that value against the given regexp
	 *
	 * @param string $sRegexp
	 * @param string $mVal
	 */
	public static function isRegexp($sRegexp, $mVal)
	{
		return filter_var($mVal, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $sRegexp))) !== false;
	}
}
