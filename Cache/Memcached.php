<?php
/**
 * @package Asap/Cache
 */

/**
 * Memcached cache
 *
 * @package Asap/Cache
 * @author Lideln
 */
final class Asap_Cache_Memcached extends Asap_Cache_Generic
{
	protected $oMemcached = null;



	public function __construct()
	{
		$this->oMemcached = new Memcached();
		// FIXME : gérer serveurs multiples dans la conf
		$this->oMemcached->addServer('localhost', 11211);
	}


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
			self::log('Memcached', $sKey);

		$mVal = $this->oMemcached->get($this->_getPrefix() . $sKey);
		if ($this->oMemcached->getResultCode() == Memcached::RES_NOTFOUND)
			return false;
		return $mVal;
	}

	public function set($sKey, $mVal, $iTTL = 0)
	{
		$this->oMemcached->set($this->_getPrefix() . $sKey, $mVal, $iTTL);
	}

	public function has($sKey)
	{
		$mVal = $this->oMemcached->get($this->_getPrefix() . $sKey);
		return !($this->oMemcached->getResultCode() == Memcached::RES_NOTFOUND);
	}

	public function clear($sKey)
	{
		$this->oMemcached->delete($this->_getPrefix() . $sKey);
	}

	public function flush()
	{
		return $this->oMemcached->flush();
	}

	public function active()
	{
		return true;
	}

	public function getAllKeys()
	{
		$list = array();

		$allSlabs = $memcache->getExtendedStats('slabs');
		$items = $memcache->getExtendedStats('items');
		foreach ($allSlabs as $server => $slabs)
		{
			foreach ($slabs AS $slabId => $slabMeta)
			{
				$cdump = $memcache->getExtendedStats('cachedump', (int)$slabId);
				foreach ($cdump AS $keys => $arrVal)
				{
					if (!is_array($arrVal))
						continue;
					foreach ($arrVal AS $k => $v)
						$list[$k] = 1;
				}
			}
		}

		return $list;
	}
}
