<?php
/**
 * @package Asap/I18N
 */

/**
 * Generic I18N management class
 *
 * @package Asap/I18N
 * @author Lideln
 */
abstract class Asap_I18N_Generic
{
	protected static $oInstance = null;

	protected $_locale = null;
	protected $_params = null;
	protected $_oldLocale = null;


	/**
	 * Change the current locale
	 *
	 * @param string $sLocale
	 */
	public function setLocale($sLocale, $bReload = false)
	{
		if ($sLocale == $this->_locale)
			return;

		$this->_oldLocale = $this->_locale;
		$this->_locale = $sLocale;
		$_SESSION['asap_locale'] = $sLocale;
		$GLOBALS['asap']->updateCurrentLanguage();

		if ($bReload)
			$this->_init();

		return $this->_oldLocale;
	}

	/**
	 * Revert to the previous locale
	 *
	 * @param unknown_type $bReload
	 */
	public function revertLocale($bReload = false)
	{
		if (empty($this->_oldLocale))
			return;

		$this->setLocale($this->_oldLocale, $bReload);
		$this->_oldLocale = null;
	}

	/**
	 * Get the current locale
	 */
	public function getLocale()
	{
		if (empty($this->_locale))
		{
			if (!empty($_SESSION['asap_locale']))
				$this->_locale = $_SESSION['asap_locale'];
			if (!empty($_SERVER['ASAP_LOCALE']))
				$this->_locale = $_SERVER['ASAP_LOCALE'];
			else
			{
				// Try to autodetect language
				$sLng = Asap_Util_Locale::getPreferredLocale();

				// If not found, use fallback language
				if (empty($sLng))
					$sLng = $this->_params['fallback'];

				// Update variable in session for further use
				$this->_locale = $sLng;
			}
			$_SESSION['asap_locale'] = $this->_locale;
			$GLOBALS['asap']->updateCurrentLanguage();
		}

		return $this->_locale;
	}



	protected function __construct($aParams)
	{
		$this->_params = $aParams;
		$this->getLocale();
		$this->_init();
	}

	public static function getInstance(&$aParams)
	{
		if (self::$oInstance == null)
		{
			$sClass = 'Asap_I18N_' . $aParams['class'];
			self::$oInstance = new $sClass($aParams);
			$GLOBALS['asap_i18n'] = self::$oInstance;
		}

		return self::$oInstance;
	}

	/**
	 * Use parameters
	 *
	 * @param string $sMsg Translated string
	 * @param array $aParams Parameters
	 */
	protected function _paramTrans($sMsg, &$aParams)
	{
		// No parameters
		if (empty($aParams))
			return $sMsg;

		// String parameter
		if (is_string($aParams))
			return sprintf($sMsg, $aParams);

		// Indexed array parameters
		if (is_int($aParams) || is_int(key($aParams)))
			return vsprintf($sMsg, $aParams);

		// Named parameters
		return str_replace(array_keys($aParams), array_values($aParams), $sMsg);
	}


	abstract protected function _init();
	abstract public function trans($sMsg, $aParams = array());
}

