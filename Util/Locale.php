<?php


class Asap_Util_Locale
{
	protected static $_initialized = false;

	protected static $_locales = array();
	protected static $_languages = array();
	protected static $_countries = array();



	/**
	 * Get all locales accepted by browser in order of preference
	 *
	 * @return array
	 */
	public static function getAllLocales()
	{
		self::_init();
		return self::$_locales;
	}

	/**
	 * Get the first locale (the preferred one)
	 *
	 * @return string
	 */
	public static function getPreferredLocale()
	{
		self::_init();
		return self::$_locales[0];
	}

	/**
	 * Get all countries
	 *
	 * @return array
	 */
	public static function getAllCountries()
	{
		self::_init();
		return self::$_countries;
	}

	/**
	 * Get preferred country
	 *
	 * @return string
	 */
	public static function getPreferredCountry()
	{
		self::_init();
		return self::$_countries[0];
	}



	/**
	 * Init the values
	 */
	protected static function _init()
	{
		if (self::$_initialized)
			return;

		self::$_initialized = true;

		// Retrieve from session if available
		if (!empty($_SESSION['asap_l10n']))
		{
			self::$_locales = $_SESSION['asap_l10n']['locales'];
			self::$_languages = $_SESSION['asap_l10n']['languages'];
			self::$_countries = $_SESSION['asap_l10n']['countries'];
			return;
		}

		self::$_locales = array();
		self::$_languages = array();
		self::$_countries = array();

		$sStr = '';
		if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
			$sStr = str_replace(';', ',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
		$aList = explode(',', $sStr);
		foreach ($aList as $sKey)
		{
			if (strpos($sKey, '_') !== false)
				self::$_locales[] = $sKey;
			if (strpos($sKey, '-') !== false)
			{
				list($s1, $s2) = explode('-', $sKey);
				self::$_locales[] = $s1 . '_' . strtoupper($s2);
			}
		}

		// Just in case...
		if (empty(self::$_locales))
			self::$_locales = array('en_US');

		foreach (self::$_locales as $sLocale)
		{
			list($sLng, $sCountry) = explode('_', $sLocale);
			if (!in_array($sLng, self::$_languages))
				self::$_languages[] = $sLng;
			if (!in_array($sCountry, self::$_countries))
				self::$_countries[] = $sCountry;
		}

		// Update session
		$_SESSION['asap_l10n'] = array(
			'locales' => self::$_locales,
			'languages' => self::$_languages,
			'countries' => self::$_countries
		);
	}
}
