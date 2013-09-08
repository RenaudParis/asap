<?php

/**
 * Twig view class
 *
 * @author Lideln
 */
class Asap_View_Twig extends Asap_View_Generic
{
	protected static $_twig;


	public function __construct()
	{
		self::_initTwig();
	}

	protected function _display($sTemplate, &$aParams, $bReturn = false)
	{
		if (!empty($aParams))
		{
			$aConf = self::getConf();
			if (!empty($aConf['register_globals']))
			{
				foreach ($aParams as $sKey => $mParam)
					self::$_twig->addGlobal($sKey, $mParam);
			}
		}

		if ($bReturn)
			return self::$_twig->render($sTemplate, $aParams);

		try
		{
			echo self::$_twig->render($sTemplate, $aParams);
		}
		catch (Exception $e)
		{
			$sMsg = $e->getMessage();
			if (!strncmp($sMsg, 'Unable to find template', 23))
			{
				file_put_contents(ASAP_DIR_LOG . 'twig-404.log', '[' . date('Y-m-d H:i:s') . '] [' . Asap_Util_Util::getIP() . '] ' . (empty($_SERVER['REDIRECT_URL']) ? 'n/a' : $_SERVER['REDIRECT_URL']) . "\r\nFrom : " . (empty($_SERVER['HTTP_REFERER']) ? 'n/a' : $_SERVER['HTTP_REFERER']) . "\r\nTemplate : " . $sTemplate . "\r\n" . $sMsg . "\r\n\r\n", FILE_APPEND);
				Asap_Core_Asap::getInstance()->throw404Error($sMsg);
			}
			else
			{
				file_put_contents(ASAP_DIR_LOG . 'twig-500.log', '[' . date('Y-m-d H:i:s') . '] [' . Asap_Util_Util::getIP() . '] ' . (empty($_SERVER['REDIRECT_URL']) ? 'n/a' : $_SERVER['REDIRECT_URL']) . "\r\nFrom : " . (empty($_SERVER['HTTP_REFERER']) ? 'n/a' : $_SERVER['HTTP_REFERER']) . "\r\nTemplate : " . $sTemplate . "\r\n" . $sMsg . "\r\n\r\n", FILE_APPEND);
				Asap_Core_Asap::getInstance()->throw500Error($sMsg);
			}
			return;
		}
	}


	/**
	 * Clear Twig cache
	 */
	public function clearCache()
	{
		self::$_twig->clearCacheFiles();
	}


	protected static function getConf()
	{
		$oAsap = $GLOBALS['asap'];
		$oCache = $GLOBALS['asap_cache'];
		$sKey = 'asap.config.twig';
		if ($oCache->has($sKey))
			return $oCache->get($sKey);

		$aConf = $oAsap->getParameter('view.engines.twig', 'asap');
		$oCache->set($sKey, $aConf);
		return $aConf;
	}

	/**
	 * Init the Twig library
	 */
	protected static function _initTwig()
	{
		if (!empty(self::$_twig))
			return;

		require_once(ASAP_DIR_VENDOR . 'Twig/Autoloader.php');
		Twig_Autoloader::register();

		$oAsap = $GLOBALS['asap'];//Asap_Core_Asap::getInstance();
		$bDebug = $oAsap->isDebug();

		$aConf = self::getConf();

		//$mDirs = ($bDebug ? array(ASAP_DIR_VIEW, ASAP_MAIN_DIR . 'tpl/') : ASAP_DIR_VIEW);
		$mDirs = array(ASAP_DIR_VIEW, ASAP_MAIN_DIR . 'tpl/');
		self::$_twig = new Twig_Environment(new Twig_Loader_Filesystem($mDirs), array(
			'cache' => ($GLOBALS['asap']->isDebug() ? false : ASAP_DIR_CACHE . 'twig/'),
			'debug' => $bDebug,
			'strict_variables' => $bDebug,
			'autoescape' => $aConf['autoescape']
		));

		$oTwig = self::$_twig;

		$oTwig->addGlobal('asap', $oAsap);
		$oTwig->addGlobal('app', $GLOBALS['asap_controller']);
		$oTwig->addGlobal('ASAP_WWW_ROOT', $GLOBALS['asap']->getWebRoot());
		// Load globals
		if (!empty($aConf['globals']))
		{
			foreach ($aConf['globals'] as $sGlobal => $mValue)
				$oTwig->addGlobal($sGlobal, $mValue);
		}

		$aControllerGlobals = $GLOBALS['asap_controller']->getViewGlobals();
		if (!empty($aControllerGlobals) && is_array($aControllerGlobals))
		{
			foreach ($aControllerGlobals as $sGlobal => $mValue)
				$oTwig->addGlobal($sGlobal, $mValue);
		}

		// Load functions
		$oTwig->addFunction('static', new Twig_Function_Function('Asap_View_Twig::fnStatic'));
		$oTwig->addFunction('date_diff', new Twig_Function_Function('Asap_View_Twig::fnDateDiff'));
		$oTwig->addFunction('include_cached', new Twig_Function_Function('Asap_View_Twig::includeCached'));
		$oTwig->addFunction('include_i18n', new Twig_Function_Function('Asap_View_Twig::includeI18n'));
		$oTwig->addFunction('use_cache', new Twig_Function_Function('Asap_View_Twig::useCache'));
		$oTwig->addFunction('save_cache', new Twig_Function_Function('Asap_View_Twig::saveCache'));
		$oTwig->addFunction('range', new Twig_Function_Function('Asap_View_Twig::fnRange'));

		// Load extensions
		if (!empty($aConf['extensions']))
		{
			//require_once(ASAP_DIR_VENDOR . 'Twig/Extensions/Autoloader.php');
			//\Twig_Extensions_Autoloader::register();
			foreach ($aConf['extensions'] as $sExt)
			{
				$sClass = 'Twig_Extensions_Extension_' . $sExt;
				$oTwig->addExtension(new $sClass());
			}
		}

		$oTwig->addFilter('trans', new Twig_Filter_Function('Asap_View_Twig::trans'));
		$oTwig->addFilter('rawurlencode', new Twig_Filter_Function('Asap_View_Twig::rawUrlEncode'));
		$oTwig->addFilter('date2fr', new Twig_Filter_Function('Asap_Util_Util::dateENtoFR'));
		$oTwig->addFilter('date2en', new Twig_Filter_Function('Asap_Util_Util::dateFRtoEN'));
		$oTwig->addFilter('ceil', new Twig_Filter_Function('Asap_View_Twig::ftCeil'));
		$oTwig->addFilter('number', new Twig_Filter_Function('Asap_Util_Util::numberFormat'));
	}


	/**
	 * Try to use cache for the given key, otherwise start caching
	 *
	 * @param string $sKey
	 * @param int $iTTL
	 */
	public static function useCache($sKey, $iTTL)
	{
		return $GLOBALS['asap_controller']->useFragmentCache($sKey, $iTTL);
	}

	public static function saveCache()
	{
		$GLOBALS['asap_controller']->saveFragmentCache();
	}

	public static function fnRange($iLow, $iHigh)
	{
		return range($iLow, $iHigh);
	}

	/**
	 * Include a template with the ability to cache the result
	 *
	 * @param string $sTemplate
	 * @param array $aNewParams
	 * @param string $sCacheKey
	 * @param int $iTTL
	 * @param bool $bUseCache
	 */
	public static function includeCached($sTemplate, array $aNewParams = array(), $iTTL = false, $sCacheKey = null, $bUseCache = true)
	{
		if (empty(self::$_twig))
			return;

		// Compute the cache key
		if (empty($sCacheKey))
		{
			$sCacheKey = str_replace('/', '_', $sTemplate);
			$sCacheKey .= implode('_', $aNewParams);
		}
		$sCacheKey = 'twig_template_' . $sCacheKey;

		// Render the twig part only if not already in cache
		$oCont = $GLOBALS['asap_controller'];
		if (!$bUseCache || !$oCont->useFragmentCache($sCacheKey, $iTTL))
		{
			echo self::$_twig->render($sTemplate, $aNewParams);
			$oCont->saveFragmentCache();
		}
	}

	/**
	 * Include the proper template depending on the language
	 *
	 * @param unknown_type $sTemplate
	 * @param array $aParams
	 */
	public static function includeI18n($sTemplate, array $aParams = array())
	{
		if (empty(self::$_twig))
			return;

		$sTemplate = Asap_Core_Controller::getInstance()->getView()->getLocalizedTpl($sTemplate);
		echo self::$_twig->render($sTemplate, $aParams);
	}

	/**
	 * Obtain diff between two dates
	 */
	public static function fnDateDiff()
	{
		$aParams = func_get_args();
		/*
		$oOldestDate = new DateTime($aParams[0]);
		$oNewestDate = new DateTime(empty($aParams[1]) ? 'now' : $aParams[1]);

		return $oOldestDate->diff($oNewestDate)->days;//->format('%a');
		*/
		$tZone = new DateTimeZone('Europe/Paris');

		$dt1 = new DateTime($aParams[0], $tZone);
		$dt2 = new DateTime(empty($aParams[1]) ? 'now' : $aParams[1], $tZone);

		// use the DateTime datediff function IF we have a non-buggy version
		// there is a bug in many Windows implementations that diff() always returns
		// 6015
		if (($iRes = $dt1->diff($dt2)->days) != 6015 )
			return $iRes;

		// else let's use our own method

		$y1 = $dt1->format('Y');
		$y2 = $dt2->format('Y');
		$z1 = $dt1->format('z');
		$z2 = $dt2->format('z');

		$diff = intval($y2 * 365.2425 + $z2) - intval($y1 * 365.2425 + $z1);

		return $diff;
	}

	/**
	 * Call this using : static('TheClass.theMethod', array('param1', 'param2')), or static('TheClass.theConstant')
	 */
	public static function fnStatic()
	{
		$aParams = func_get_args();
		$sAction = $aParams[0];
		if (strpos($sAction, '.') === false || (isset($aParams[1]) && !is_array($aParams[1])))
			throw new Exception('Bad use of the static() Twig function');

		list($sClass, $sMethod) = explode('.', $sAction);
		$sAction2 = str_replace('.', '::', $sAction);
		if (method_exists($sClass, $sMethod))
		{
			$aParams = (count($aParams) == 2 ? $aParams[1] : array());
			return call_user_func_array($sAction2, $aParams);
		}
		else if (property_exists($sClass, $sMethod))
			return $sClass::$$sMethod;
		else if (defined($sAction2))
			return constant($sAction2);
		else
			throw new Exception('static() : ' . $sAction2 . ' not found');
	}

	/**
	 * Applies the rawurlencode function on an element
	 */
	public static function rawUrlEncode()
	{
		return call_user_func_array('rawurlencode', func_get_args());
	}

	/**
	 * Translation (shortcut)
	 */
	public static function ftCeil($fNumber)
	{
		return ceil($fNumber);
	}

	/**
	 * Translation (shortcut)
	 */
	public static function trans()
	{
		if (empty($GLOBALS['asap_i18n']))
			return func_get_arg(0);

		return call_user_func_array(array($GLOBALS['asap_i18n'], 'trans'), func_get_args());
	}
}
