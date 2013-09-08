<?php
/**
 * @package Asap/Cache
 */

/**
 * Cache management abstract class
 *
 * @package Asap/Cache
 * @author Lideln
 */
abstract class Asap_Cache_Generic
{
	const DURATION_1_MIN = 60;
	const DURATION_5_MIN = 300;
	const DURATION_10_MIN = 600;
	const DURATION_15_MIN = 900;
	const DURATION_30_MIN = 1800;
	const DURATION_1_HOUR = 3600;
	const DURATION_2_HOUR = 7200;
	const DURATION_6_HOUR = 21600;
	const DURATION_1_DAY = 86400;
	const DURATION_1_WEEK = 604800;
	const DURATION_1_MONTH = 2592000;

	protected static $_log = null;


	public static function getInstance()
	{
		static $oInstance = null;
		if ($oInstance == null)
		{
			$bUseCache = $GLOBALS['asap']->isUseCache();
			$oTmp = null;
			if ($bUseCache === false)
				$oTmp = new Asap_Cache_NoCache();
			else if (extension_loaded('memcached'))
				$oTmp = new Asap_Cache_Memcached();
			else if (extension_loaded('apc'))
				$oTmp = new Asap_Cache_APC();
			else
				$oTmp = new Asap_Cache_File();

			if ($bUseCache === null)
				return $oTmp;

			$oInstance = $oTmp;
			$GLOBALS['asap_cache'] = $oInstance;
		}

		return $oInstance;
	}


	/**
	 * Log a cache access
	 *
	 * @param string $sType The cache type
	 * @param string $sKey The key
	 */
	public static function log($sType, $sKey)
	{
		if (self::$_log === null)
			self::$_log = array();
		if (!isset(self::$_log[$sType]))
			self::$_log[$sType] = array();
		if (!isset(self::$_log[$sType][$sKey]))
			self::$_log[$sType][$sKey] = 0;
		self::$_log[$sType][$sKey]++;
	}

	public static function getLog()
	{
		return self::$_log;
	}

	public static function isDebugCache()
	{
		static $use = null;
		if ($use === null)
		{
			$use = $GLOBALS['asap']->getParameter('debug_cache', 'asap');
			$use = !empty($use);
		}
		return $use;
	}

	/**
	 * Check if we must use the cache prefix
	 */
	public static function isUseCachePrefix()
	{
		static $use = null;
		if ($use === null)
		{
			$use = $GLOBALS['asap']->getParameter('cache_prefix', 'asap');
			$use = !empty($use);
		}
		return $use;
	}

	/**
	 * Flush the "cache prefix list" cache
	 */
	public static function flushCachePrefix()
	{
		return self::getAllCachePrefix(true);
	}

	/**
	 * Get all the cache prefix
	 */
	public static function getAllCachePrefix($bFlush = false)
	{
		static $aPrefix = null;

		if ($aPrefix === null || $bFlush)
		{
			if (!self::isUseCachePrefix())
				$aPrefix = array();
			else
			{
				$oCache = $GLOBALS['asap_cache'];
				$sCacheKey = 'asap.cache.prefix';
				if (!$bFlush && $oCache->has($sCacheKey))
					$aPrefix = $oCache->get($sCacheKey);
				else
				{
					$aRows = Asap_Database_CachePrefixModel::getAll();
					$aPrefix = array();
					foreach ($aRows as $aRow)
						$aPrefix[$aRow['name']] = $aRow['prefix'];
					$oCache->set($sCacheKey, $aPrefix, Asap_Cache_Generic::DURATION_1_DAY);
				}
			}
		}

		return $aPrefix;
	}

	/**
	 * Add a prefix to the cache key (if active)
	 *
	 * @param string $sKey The cache key
	 * @param string $sType The prefix type
	 */
	public function usePrefix($sKey, $sType)
	{
		if (!self::isUseCachePrefix())
			return $sKey;

		$aPrefix = self::getAllCachePrefix();
		if (empty($aPrefix[$sType]))
			$aPrefix[$sType] = self::updatePrefix($sType);

		return $aPrefix[$sType] . '__' . $sKey;
	}

	/**
	 * Update the prefix for the given cache category
	 *
	 * @param string $sType
	 * @param string $sValue = null Value of the prefix (env and type will automatically be prefixed to this value)
	 */
	public static function updatePrefix($sType, $sValue = null)
	{
		if (empty($sValue))
			$sValue = time();
		$sValue = $GLOBALS['asap']->getEnvironment() . '_' . $sType . '_' . $sValue;
		Asap_Database_CachePrefixModel::updatePrefix($sType, $sValue);
		// Flush the prefix cache
		self::flushCachePrefix();
	}

	/**
	 * Update all the cache prefix at once
	 */
	public static function updateAllPrefix()
	{
		$sEnv = $GLOBALS['asap']->getEnvironment();
		$sValue = time();
		foreach (self::getAllCachePrefix() as $sType => $sVal)
			Asap_Database_CachePrefixModel::updatePrefix($sType, $sEnv . '_' . $sType . '_' . $sValue);
		// Flush the prefix cache
		self::flushCachePrefix();
	}

	/**
	 * Get a key from the cache or set it if it does not exist
	 *
	 * @param string $sKey The key to get
	 * @param array $mCallback A callback (string, array) or a Closure
	 * @param array $aParams[optional] Parameters for a standard callback
	 * @param int $iTTL Time to live
	 */
	public function getSet($sKey, $mCallback, $aParams = array(), $iTTL = 0)
	{
		if ($this->has($sKey))
		{
			return $this->get($sKey);
		}
		else
		{
			if (is_string($mCallback) || is_array($mCallback)) // Callback
				$mVal = call_user_func_array($mCallback, $aParams);
			else // Closure
				$mVal = $mCallback();
			$this->set($sKey, $mVal, $iTTL);
			return $mVal;
		}
	}

	/**
	 * Shortcut for the getSet non-static function
	 */
	public static function s_getSet($sKey, $mCallback, $aParams = array(), $iTTL = 0)
	{
		$oCache = self::getInstance();//$GLOBALS['asap_cache'];
		return $oCache->getSet($sKey, $mCallback, $aParams, $iTTL);
	}

	public function active() { return false; }
	public function get($sKey) { return false; }
	public function set($sKey, $mVal, $iTTL = 0) {}
	public function has($sKey) { return false; }
	public function clear($sKey) {}
	public function flush() {}
}
