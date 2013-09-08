<?php


/**
 * Main view class
 *
 * @author Lideln
 */
abstract class Asap_View_Generic
{
	protected static $_config = null;
	protected static $_static_url = null;



	/**
	 * Display the proper view
	 *
	 * @param Route $oRoute The current route
	 * @param array $aParams The view parameters
	 */
	final public static function factory(Asap_Core_Route $oRoute, $mData)
	{
		$aParams = array();
		$oResponse = null;
		if (is_array($mData))
			$aParams = $mData;
		else
			$oResponse = $mData;

		$sTemplate = null;
		$sViewEngine = null;
		$aViewParams = self::getConfig();
		// Case of a default view rendering
		if (empty($oResponse))
		{
			// Get view parameters
			$oAsap = $GLOBALS['asap'];
			$sViewEngine = $aViewParams['default_engine'];
			if ($oAsap->isDebug() && empty($aViewParams['engines'][$sViewEngine]))
				throw new Exception('View engine "' . $sViewEngine . '" is not configured');
			$aConf = $aViewParams['engines'][$sViewEngine];
			$sTemplate = str_replace(array('{controller}', '{action}'), array($oRoute->getController(), $oRoute->getAction()), $aConf['naming']);
		}
		else // Case of a given Response object
		{
			$sViewEngine = $oResponse->getEngine();
			$sTemplate = $oResponse->getTemplate();
			$aParams = &$oResponse->getParams();
		}


		// Render the template
		$sClass = ucfirst($sViewEngine);
		// FIXME : gérer les répertoires de l'utilisateur (pour de nouveaux types de vues custom, genre Smarty, etc.)

		//if ($oAsap->isDebug() && !file_exists(ASAP_DIR_VIEW . $sTemplate))
		//	throw new Exception('Template "' . $sTemplate . '" does not exist');

		//require_once($sFile);
		$sClass = 'Asap_View_' . $sClass;
		$oView = new $sClass();
		Asap_Core_Controller::getInstance()->setView($oView);
		// Try to load a language-specific template

		// Manage views for mobile content
		if (!empty($sTemplate) && !empty($aViewParams['mobile']) && Asap_Util_Util::isMobileDevice())
		{
			// Only if we have a mobile version of the template
			$aConf = $oView->getEngineConfig();
			$sMobileTpl = 'mobile/' . $sTemplate . $aConf['extension'];
			if ($oView->hasTemplate($sMobileTpl))
				$sTemplate = $sMobileTpl;
		}

		$sTemplate = $oView->getLocalizedTpl($sTemplate);
		$oView->render($sTemplate, $aParams);
	}


	/**
	 * Clear the configuration cache
	 */
	public static function clearConfCache()
	{
		$oCache = $GLOBALS['asap_cache'];
		$oCache->clear('asap.config.view');
		$oCache->clear('asap.config.view.static_url');
	}

	/**
	 * Get the view configuration parameters
	 */
	public static function getConfig()
	{
		if (self::$_config === null)
			self::$_config = $GLOBALS['asap_cache']->getSet('asap.config.view', 'Asap_View_Generic::_cache_getConf');
		return self::$_config;
	}

	/**
	 * Get the static files root URL (optionally using a CDN)
	 */
	public static function getStaticRoot()
	{
		if (self::$_static_url === null)
			self::$_static_url = $GLOBALS['asap_cache']->getSet('asap.config.view.static_url', 'Asap_View_Generic::_cache_getConfStaticURL');
		return self::$_static_url;
	}

	/**
	 * Get the view configuration parameters for the given engine
	 *
	 * @param string $sEngine
	 */
	public static function getConfigFor($sEngine)
	{
		$aParams = self::getConfig();
		if (!isset($aParams['engines'][$sEngine]))
			throw new Exception('View engine "' . $sEngine . '" does not exist in configuration');
		return $aParams['engines'][$sEngine];
	}

	/**
	 * Get the config for this engine
	 */
	public function getEngineConfig()
	{
		$sEngine = strtolower(substr(get_class($this), 10));
		return self::getConfigFor($sEngine);
	}

	/**
	 * Try to find a localized template for the given language
	 *
	 * @param string $sTpl
	 * @param string $sLng Default null : current language
	 */
	public function getLocalizedTpl($sTpl, $sLng = null)
	{
		if ($sLng === null)
			$sLng = $GLOBALS['asap']->getCurrentLanguage();

		$aConf = $this->getEngineConfig();
		$sExt = $aConf['extension'];
		// Try to remove extension if it exists (in order to be able to check for a localized template)
		$sTpl = Asap_Util_Util::trimEnd($sTpl, $sExt);
		$sLngTpl = $sTpl . '_' . $sLng . $sExt;
		if ($this->hasTemplate($sLngTpl))
			return $sLngTpl;
		return $sTpl . $sExt;
	}


	/**
	 * Render a template
	 *
	 * @param unknown_type $sTemplate
	 * @param array $aParams
	 */
	public function render($sTemplate, &$aParams, $bReturn = false)
	{
		$this->_init();
		return $this->_display($sTemplate, $aParams, $bReturn);
	}

	/**
	 * Display a template
	 *
	 * @param string $sTemplate
	 * @param array $aParams
	 */
	abstract protected function _display($sTemplate, &$aParams);

	/**
	 * Check if the view can render the given template
	 *
	 * @param unknown_type $sTemplate
	 */
	public function hasTemplate($sTemplate)
	{
		return file_exists(ASAP_DIR_VIEW . $sTemplate);
	}

	/**
	 * Init the renderer
	 */
	protected function _init() {}

	/**
	 * Clear cache
	 */
	public function clearCache() {}






	public static function _cache_getConf()
	{
		return $GLOBALS['asap']->getParameter('view', 'asap');
	}

	public static function _cache_getConfStaticURL()
	{
		self::getConfig();
		$aArr = self::$_config['cdn'];
		if (!empty($aArr['active']) && !empty($aArr['use']) && array_key_exists($aArr['use'], $aArr['list']))
		{
			$sURL = $aArr['list'][$aArr['use']];
			if (substr($sURL, -1) != '/')
				$sURL .= '/';
			return $sURL;
		}
		return $GLOBALS['asap']->getWebRoot();
	}

	// FIXME : faire une fonction displayText
}
