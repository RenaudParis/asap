<?php
/**
 * @package Asap/Cache
 */

/**
 * APC cache
 *
 * @package Asap/Cache
 * @author Lideln
 */
final class Asap_Cache_APC extends Asap_Cache_Generic
{
	protected function _getPrefix()
	{
		static $sPrefix = null;
		if ($sPrefix === null)
		{
			$sPrefix = $GLOBALS['asap']->getParameter('application.cache_prefix', 'asap');
			if (empty($sPrefix))
				$sPrefix = '';
		}
		return $sPrefix;
	}


	public function get($sKey)
	{
		if (self::isDebugCache())
			self::log('APC', $sKey);
		return apc_fetch($this->_getPrefix() . $sKey);
	}

	public function set($sKey, $mVal, $iTTL = 0)
	{
		apc_store($this->_getPrefix() . $sKey, $mVal, $iTTL);
	}

	public function has($sKey)
	{
		return apc_exists($this->_getPrefix() . $sKey);
	}

	public function clear($sKey)
	{
		apc_delete($this->_getPrefix() . $sKey);
	}

	public function flush()
	{
		return apc_clear_cache('user');
	}

	public function active()
	{
		return true;
	}
}
