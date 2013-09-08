<?php
/**
 * @package Asap/I18N
 */

/**
 * PHP Array I18N class
 *
 * @package Asap/I18N
 * @author Lideln
 */
class Asap_I18N_Array extends Asap_I18N_Generic
{
	protected $_trans = null;
	protected $_messages = null;



	public function trans($sMsg, $aParams = array())
	{
		if (empty($sMsg))
			return $sMsg;
		// Try to load the translated string
		if (isset($this->_messages[$sMsg]))
			return $this->_paramTrans($this->_messages[$sMsg], $aParams);

		return $sMsg;
	}




	/**
	 * Initialize (load translation array)
	 */
	protected function _init()
	{
		// Try to load from cache first
		$bWasFromCache = false;
		$aMessages = $this->_loadLocale($this->_locale, $bWasFromCache);

		if ($bWasFromCache == true)
		{
			$this->_messages = $aMessages;
			return;
		}

		// Not loaded in cache, load fallback i18n first
		$sFallback = $this->_params['fallback'];
		if ($sFallback != $this->_locale)
		{
			$this->_messages = $this->_loadLocale($sFallback, $bWasFromCache);
			if (!$bWasFromCache)
				$this->_updateCache($sFallback);
		}
		else
			$this->_messages = $aMessages;

		if ($this->_messages === false)
		{
			if ($GLOBALS['asap']->isDebug())
				throw new Exception('Default language file not found for locale ' . $sFallback . ' : ' . $this->_getFileName($sFallback));
			else
				$this->_messages = array();
		}
		// Change locale to use fallback because no translation file was found for the given language
		if ($aMessages === false)
		{
			if ($GLOBALS['asap']->isDebug())
				throw new Exception('Language file not found for locale ' . $this->_locale . ' : ' . $this->_getFileName($this->_locale) . ' (in non-debug environment, we would have silently switched to fallback language)');
			$this->setLocale($sFallback);
		}

		// Return is fallback is current language
		if ($sFallback == $this->_locale)
			return;

		// If we want to load a language other than fallback, update the cache
		$this->_messages = array_merge($this->_messages, $aMessages);
		$this->_updateCache($this->_locale);
	}

	/**
	 * Try to load a locale
	 *
	 * @param unknown_type $sLocale
	 */
	protected function _loadLocale($sLocale, &$bWasFromCache)
	{
		$oCache = $GLOBALS['asap_cache'];
		$sKey = $oCache->usePrefix('asap.i18n.' . $sLocale, 'asap_i18n');
		if ($oCache->has($sKey))
		{
			$bWasFromCache = true;
			return $oCache->get($sKey);
		}

		$bWasFromCache = false;

		$sFile = $this->_getFileName($sLocale);
		if (!file_exists($sFile))
			return false;

		require($sFile);

		if (empty($lc_messages))
			return array();

		return $lc_messages;
	}

	protected function _getFileName($sLocale)
	{
		list($sLng, $sCountry) = explode('_', $sLocale);
		$sBasename = str_replace(array('%domain%', '%locale%', '%language%', '%country%'), array($this->_params['domain'], $sLocale, $sLng, $sCountry), $this->_params['files']);
		return ASAP_DIR_I18N . $sBasename . '.php';
	}

	/**
	 * Update the cache
	 *
	 * @param unknown_type $sLocale
	 */
	protected function _updateCache($sLocale)
	{
		$oCache = $GLOBALS['asap_cache'];
		$sKey = $oCache->usePrefix('asap.i18n.' . $sLocale, 'asap_i18n');
		$oCache->set($sKey, $this->_messages);
	}
}
