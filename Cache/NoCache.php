<?php
/**
 * @package Asap/Cache
 */

/**
 * No cache
 *
 * @package Asap/Cache
 * @author Lideln
 */
final class Asap_Cache_NoCache extends Asap_Cache_Generic
{
	public function has($sKey)
	{
		if (self::isDebugCache())
			self::log('Memcached', '(no cache) ' . $sKey);

		return false;
	}
}
