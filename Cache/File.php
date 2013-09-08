<?php
/**
 * @package Asap/Cache
 */

/**
 * File cache
 *
 * @package Asap/Cache
 * @author Lideln
 */
final class Asap_Cache_File extends Asap_Cache_Generic
{
	public function get($sKey, $bUnserialize = false)
	{
		$sFile = ASAP_DIR_CACHE . $sKey;
		if (self::isDebugCache())
		{
			self::log('File', $sKey);
			/*
			var_dump('MODE DEBUG : CACHE !!');
			var_dump('On utilise du cache fichier existant pour la clef : ' . $sKey);
			var_dump('Le cache existe depuis ' . (time() - filemtime($sFile)) . ' secondes');
			*/
		}
		$mContents = file_get_contents($sFile);
		if ($bUnserialize)
			$mContents = unserialize($mContents);

		return $mContents;
	}

	public function set($sKey, $mVal, $iTTL = 0, $bSerialize = false)
	{
		if ($bSerialize)
			$mVal = serialize($mVal);
		/*
		if (false && self::isDebugCache())
		{
			var_dump('MODE DEBUG : CACHE !!');
			var_dump('On crée du cache fichier pour la clef : ' . $sKey);
			var_dump('Le cache existera ' . ($iTTL == 0 ? 'indéfiniment' : $iTTL . ' secondes'));
		}
		*/
		$aVals = pathinfo($sKey);
		if (!empty($aVals['dirname']))
		{
			$sPath = ASAP_DIR_CACHE . $aVals['dirname'];
			if (!file_exists($sPath))
				@mkdir($sPath, 0775, true);
		}
		file_put_contents(ASAP_DIR_CACHE . $sKey, $mVal);
	}

	public function has($sKey)
	{
		$sFile = ASAP_DIR_CACHE . $sKey;
		return file_exists($sFile) ? $sFile : false;
	}

	/**
	 * Check if cache file is still fresh
	 *
	 * @param string $sKey
	 * @param int $iTTL
	 */
	public function isFresh($sKey, $iTTL = 0)
	{
		$iAge = $this->getAge($sKey);

		// File not found, not fresh !
		if ($iAge === false)
			return false;

		// Unlimited cache, always fresh !
		if ($iTTL <= 0)
			return true;

		// Fresh only if age < ttl
		return $iTTL > $iAge;
	}

	public function getAge($sKey)
	{
		$sFile = $this->has($sKey);
		if (!$sFile)
			return false;
		return time() - filemtime($sFile);
	}

	public function clear($sKey)
	{
		if (file_exists(ASAP_DIR_CACHE . $sKey))
			@unlink(ASAP_DIR_CACHE . $sKey);
	}

	public function flush()
	{
		Asap_Util_Util::rmdir(ASAP_DIR_CACHE, false);
	}

	public function active()
	{
		return true;
	}
}
