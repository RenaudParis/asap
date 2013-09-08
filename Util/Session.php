<?php

/**
 * Manages session values with TTL etc.
 *
 * @author Renaud
 */
class Asap_Util_Session
{
	/**
	 * Stores a value in the session
	 *
	 * @param string $sKey
	 * @param mixed $mVal
	 * @param int $iTTL Time to live (if necessary)
	 */
	public static function set($sKey, $mVal, $iTTL = 0)
	{
		$_SESSION[$sKey] = $mVal;
		if (!empty($iTTL) && is_numeric($iTTL) && $iTTL > 0)
			$_SESSION[$sKey . '_ttl'] = time() + $iTTL;
	}

	/**
	 * Retrieves a value from the session, and optionally sets its new value if needed
	 *
	 * @param string $sKey The key
	 * @param string $mNewVal[optional] The new hard-coded value
	 * @param int $iTTL[optional] TTL of this info
	 * @param function $pCallback[optional] Function to call and get the new value to set
	 * @param array $aCallbackParams[optional] Optional parameters for the callback
	 */
	public static function get($sKey, $mNewVal = null, $iTTL = 0, $pCallback = null, $aCallbackParams = null)
	{
		if (!self::has($sKey))
		{
			if ($pCallback)
			{
				if (is_string($pCallback) || is_array($pCallback)) // Callback
					$mNewVal = call_user_func_array($pCallback, $aCallbackParams);
				else // Closure
					$mNewVal = $pCallback();
			}

			if (!empty($mNewVal))
				self::set($sKey, $mNewVal, $iTTL);

			// Can be either null (for non-existing) or any newly set value
			return $mNewVal;
		}

		return $_SESSION[$sKey];
	}

	/**
	 * Check if there is a value
	 *
	 * @param string $sKey
	 */
	public static function has($sKey)
	{
		if (!isset($_SESSION[$sKey]))
			return false;

		if (isset($_SESSION[$sKey . '_ttl']) && $_SESSION[$sKey . '_ttl'] < time())
			return false;

		return true;
	}

	/**
	 * Clear all session data
	 */
	public static function clear()
	{
		$_SESSION = array();
	}

	/**
	 * Remove a session data
	 *
	 * @param string $sKey
	 */
	public static function del($sKey)
	{
		if (isset($_SESSION[$sKey]))
			unset($_SESSION[$sKey]);
		if (isset($_SESSION[$sKey . '_ttl']))
			unset($_SESSION[$sKey . '_ttl']);
	}
}
