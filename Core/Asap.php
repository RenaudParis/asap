<?php
/**
 * @package Asap/Core
 */


$sPath = str_replace('\\', '/', getcwd()) . '/';

define('ASAP_VERSION', '0.1.1a');
define('ASAP_DIR_ROOT', $sPath);
define('ASAP_DIR_VENDOR', ASAP_DIR_ROOT . 'vendor/');
define('ASAP_MAIN_DIR', ASAP_DIR_VENDOR . 'Asap/');

define('ASAP_DIR_CACHE', ASAP_DIR_ROOT . 'cache/');

define('ASAP_APP', isset($_SERVER['ASAP_APP']) ? $_SERVER['ASAP_APP'] : '');

define('ASAP_APP_SUBFOLDER', ASAP_APP ? ASAP_APP . '/' : '');

define('ASAP_DIR_WEB', ASAP_DIR_ROOT . 'web/');
define('ASAP_DIR_LOG', ASAP_DIR_ROOT . 'log/');
define('ASAP_DIR_APP', ASAP_DIR_ROOT . 'app/');

define('ASAP_DIR_CONFIG', ASAP_DIR_APP . 'config/' . ASAP_APP_SUBFOLDER);
define('ASAP_DIR_MODEL', ASAP_DIR_APP . 'model/');
if (file_exists(ASAP_DIR_APP . 'view/' . ASAP_APP_SUBFOLDER))
	define('ASAP_DIR_VIEW', ASAP_DIR_APP . 'view/' . ASAP_APP_SUBFOLDER);
else
	define('ASAP_DIR_VIEW', ASAP_DIR_APP . 'view/');
define('ASAP_DIR_CONTROLLER', ASAP_DIR_APP . 'controller/' . ASAP_APP_SUBFOLDER);
define('ASAP_DIR_LIB', ASAP_DIR_APP . 'lib/');
define('ASAP_DIR_I18N', ASAP_DIR_APP . 'i18n/');

define('ASAP_WWW_ROOT', 'http://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1') . '/');


define('ASAP_USE_CLASSES_CACHE', (isset($_SERVER['ASAP_ENV']) && strtolower($_SERVER['ASAP_ENV']) == 'prod'));


//require_once(ASAP_MAIN_DIR . 'Util/Cache.php');


/**
 * Core class of the ASAP framework
 *
 * @package Asap/Core
 * @author Lideln
 */
final class Asap_Core_Asap
{
	/**
	 * Instance
	 *
	 * @var Asap
	 */
	protected static $_instance = null;

	/**
	 * Current language
	 *
	 * @var string
	 */
	protected $_currentLng = null;

	/**
	 * Environment (dev / test / prod)
	 *
	 * @var string
	 */
	protected $_environment = '';

	/**
	 * Global conf (asap + app)
	 *
	 * @var array
	 */
	protected $_conf = null;

	/**
	 * Routes
	 *
	 * @var array
	 */
	protected $_routes = null;

	/**
	 * Current route
	 *
	 * @var Asap_Core_Route
	 */
	protected $_route = null;

	/**
	 * Current controller
	 *
	 * @var Asap_Core_Controller
	 */
	protected $_controller = null;

	/**
	 * Cache
	 *
	 * @var ACache
	 */
	protected $_cache = null;

	/**
	 * Databases
	 *
	 * @var array
	 */
	protected $_databases = null;

	/**
	 * I18N params
	 *
	 * @var array
	 */
	protected $_i18n = null;

	/**
	 * Debug bar
	 *
	 * @var Asap_Util_DebugBar
	 */
	protected $_debugBar = null;
	/**
	 * Optional and temporary web root
	 */
	protected $_customWebRoot = null;


	/**
	 * Time for the debug bar
	 * @var unknown_type
	 */
	public static $fBeginTime = null;





	/**
	 * Initialize the framework
	 */
	public static function init()
	{
		self::$fBeginTime = microtime(true);

		// Set our instance properly
		self::$_instance = new self();

		// Faster access
		$GLOBALS['asap'] = self::$_instance;

		// Actually initialize the framework
		self::$_instance->_initialize();

		return self::$_instance;
	}

	/**
	 * Check if mobile (or tablet)
	 */
	public function isMobileDevice()
	{
		return Asap_Util_Util::isMobileDevice();
	}

	/**
	 * Find the matching route for the current page and launch the associated action
	 */
	public function routeAndLaunch()
	{
		// Find route
		$this->_matchRoute();

		//var_dump($this->_route);
		$aTmpFlash = (empty($_SESSION['flash']) ? array() : $_SESSION['flash']);

		// Load corresponding controller and execute corresponding action
		$this->_launchAction();

		// Remove flash variables
		Asap_Core_Controller::clearFlash($aTmpFlash);

		//echo '<pre>', print_r($_SERVER, true), '</pre>';
	}


	/**
	 * Get the current route
	 *
	 * @return Asap_Core_Route
	 */
	public function getRoute()
	{
		return $this->_route;
	}

	/**
	 * Get the current controller
	 *
	 * @return Asap_Core_Controller
	 */
	public function getController()
	{
		return $this->_controller;
	}

	/**
	 * Get the active environment
	 */
	public function getEnvironment()
	{
		return $this->_environment;
	}

	/**
	 * Get the configuration data
	 */
	public function getConf()
	{
		return $this->_conf->getData();
	}

	/**
	 * Get an application parameter
	 *
	 * @param string $sKey Key of the parameter to find
	 * @param string $sFrom Optional, where to search for it (default "app")
	 */
	public function getParameter($sKey, $sFrom = 'app')
	{
		if (empty($this->_conf))
			return null;
		return $this->_conf->get($sFrom . (!empty($sKey) ? '.' . $sKey : ''));
	}

	/**
	 * Check if we are using cache
	 */
	public function isUseCache()
	{
		if (empty($this->_conf))
			return null;
		return $this->_conf->get('asap.cache');//['asap']['cache'];
	}

	/**
	 * Check if we are in debug mode
	 */
	public function isDebug()
	{
		static $bDebug = null;
		if ($bDebug === null)
			$bDebug = $this->_conf->get('asap.debug');//['asap']['debug'];
		return $bDebug;
	}

	/**
	 * Check if we are allowed to show the debug bar
	 */
	public function isDebugBarAllowed()
	{
		static $bDebugBar = null;
		if ($bDebugBar === null)
			$bDebugBar = $this->_conf->get('asap.debug_bar');
		return $bDebugBar;
	}

	/**
	 * Show the debug bar
	 */
	public final function showDebugBar()
	{
		// Only in debug mode
		if (!$this->isDebugBarAllowed())
			return;

		if ($this->_debugBar === null)
			$this->_debugBar = Asap_Util_DebugBar::getInstance();

		$this->_debugBar->show();
	}

	/**
	 * Get cache
	 */
	public function getCache()
	{
		throw new Exception('interdit !!! (passer plus rapidement par $GLOBALS["asap_cache"])');
		return Asap_Cache_Generic::getInstance();
	}

	/**
	 * Get a database
	 *
	 * @param string $sId
	 */
	public function getDB($sId = 'main')
	{
		if (empty($this->_databases[$sId]))
			return null;

		if (empty($this->_databases[$sId]['pdo']))
		{
			$aInfo = $this->_databases[$sId]['info'];
			$aInfo['debug'] = $this->isDebug();
			$this->_databases[$sId]['pdo'] = new Asap_Database_DB($aInfo);
		}

		return $this->_databases[$sId]['pdo'];
	}

	/**
	 * Load a package (JS or CSS)
	 *
	 * @param $sType
	 */
	public function getPackage($sType, $bUseTags = true)
	{
		Asap_Util_Packager::loadPackage($sType, $bUseTags);
	}

	/**
	 * Get the controller name
	 */
	public function getControllerName()
	{
		if (empty($this->_route))
			$this->throw500Error('Trying to get controller name of an empty route');
		return ucfirst($this->_route->getController());
	}










	public function updateCurrentLanguage()
	{
		$sLocale = 'fr_FR';
		if (!empty($_SESSION['asap_locale']))
			$sLocale = $_SESSION['asap_locale'];
		else if (!empty($_SERVER['ASAP_LOCALE']))
			$sLocale = $_SERVER['ASAP_LOCALE'];
		list($this->_currentLng, $tmp) = explode('_', $sLocale);
	}

	/**
	 * Initialize Asap framework
	 */
	protected function _initialize()
	{
		// Get current language
		$this->updateCurrentLanguage();

		// Load the standard classes
		$this->_loadClasses();

		spl_autoload_register('Asap_Core_Asap::autoload_asap');

		// Load conf
		$this->_loadConf();
		$aConf = $this->_conf->get('asap');

		// Autoloads
		$aAutoloads = $aConf['autoload'];//$this->_conf->get('asap.autoload');
		// User classes autoloader
		if ($aAutoloads['app'])
			spl_autoload_register('Asap_Core_Asap::autoload_app');
		// Asap autoloader
		if (!$aAutoloads['asap'])
			spl_autoload_unregister('Asap_Core_Asap::autoload_asap');

		// Load I18N if needed
		$this->_initI18N();

		// Define debugging tools
		$this->_setErrorHandler();

		// Load routes
		$this->_loadRoutes();

		// Init app classes cache
		$this->_loadAppClasses();

		// Start session
		$aSession = $aConf['session'];
		if (!empty($aSession['cookie_domain']))
		{
			if (is_array($aSession['cookie_domain']))
				ini_set('session.cookie_domain', $aSession['cookie_domain'][$this->_currentLng]);
			else
				ini_set('session.cookie_domain', $aSession['cookie_domain']);
		}
		if (!empty($aSession['autostart']))
		{
			if (!class_exists('Application', false))
				require_once(ASAP_DIR_CONTROLLER . 'Application.php');
			Application::onBeforeSessionStart();

			if (!empty($aSession['name']))
				session_name($aSession['name']);
			session_start();
		}

		//trigger_error('pouet');
		//echo $bla;

		//var_dump($_SERVER);

		// Actions
		//var_dump($this->_conf);
		//var_dump($this->_routes);
	}

	/**
	 * Initialize I18N if needed
	 */
	protected function _initI18N()
	{
		// Get view parameters
		$oCache = $GLOBALS['asap_cache'];
		$sKey = 'asap.config.i18n';
		if ($oCache->has($sKey))
		{
			$aI18NParams = $oCache->get($sKey);
		}
		else
		{
			$oAsap = $GLOBALS['asap'];
			$aI18NParams = $this->getParameter('i18n', 'asap');
			$aI18NParams['class'] = ucfirst($aI18NParams['method']);
			$oCache->set($sKey, $aI18NParams);
		}

		$this->_i18n = $aI18NParams;

		// If needed, initialize
		if (!empty($aI18NParams['active']))
			Asap_I18N_Generic::getInstance($aI18NParams);
	}

	/**
	 * Get the I18N params
	 */
	public function getI18NParams()
	{
		return $this->_i18n;
	}

	/**
	 * Check if a route exists
	 *
	 * @param $sRoute
	 */
	public function isRouteExist($sRoute)
	{
		$aRoutes = $this->_routes->getData();
		return isset($aRoutes[$sRoute]);
	}

	/**
	 * Get the URL matching the given route and route params
	 *
	 * @param string $sRoute
	 * @param array [optional] $aParams
	 * @param bool $bAbsolute
	 */
	public function getRouteUrl($sRoute, $aParams = array(), $bAbsolute = true, $sLng = null)
	{
		$aRoutes = $this->_routes->getData();
		if (!isset($aRoutes[$sRoute]))
		{
			//throw new Exception('Route "' . $sRoute . '" not found');

			// Assume it is a generic URL with no associated route...
			$sUrl = $sRoute;
		}
		else
		{
			// ... Otherwise it has a route and we can compute its URL
			$aRoute = $aRoutes[$sRoute];
			$sTmpLng = (empty($sLng) ? $this->_currentLng : $sLng);
			if (!empty($sTmpLng) && isset($aRoute['i18n'][$sTmpLng]))
				$sUrl = $aRoute['i18n'][$sTmpLng]['url'];
			else
				$sUrl = $aRoute['url'];
		}
		if (empty($sUrl))
			throw new Exception('URL "' . $sRoute . '" not found');
		if ($bAbsolute || !empty($sLng))
		{
			if (!empty($sLng))
			{
				$sHost = $this->getHostForLanguage($sLng);
				$sHost = $this->_controller->hydrateHost($sHost);
			}
			else
				$sHost = $this->getWebRoot();
			$sUrl = $sHost . ($sUrl[0] == '/' ? substr($sUrl, 1) : $sUrl);
		}
		if (empty($aParams))
			return $sUrl;
		$aParams2 = array();
		foreach ($aParams as $sKey => $sVal)
			$aParams2['{' . $sKey . '}'] = $sVal;
		return str_replace(array_keys($aParams2), array_values($aParams2), $sUrl);
	}


	/**
	 * Get the current web root (or an optional temporary custom web root)
	 */
	public function getWebRoot()
	{
		return (empty($this->_customWebRoot) ? ASAP_WWW_ROOT : $this->_customWebRoot);
	}

	/**
	 * Check if we are in HTTPS
	 */
	public static function isHTTPS()
	{
		static $bRes = null;
		if ($bRes === null)
			$bRes = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
		return $bRes;
	}

	/**
	 * Set the custom web root
	 *
	 * @param string $sRoot The web root (eg: http://www.mywebsite.com/)
	 */
	public function setCustomWebRoot($sRoot)
	{
		$this->_customWebRoot = $sRoot;
		if (substr($sRoot, -1) != '/')
			$this->_customWebRoot .= '/';
	}

	/**
	 * Clear the custom web root
	 */
	public function resetWebRoot()
	{
		$this->_customWebRoot = null;
	}


	/**
	 * Shortcut for getRouteUrl
	 *
	 * @param $sRoute
	 * @param $aParams
	 * @param $bAbsolute
	 * @param $sLng
	 */
	public function getUrl($sRoute, $aParams = array(), $bAbsolute = true, $sLng = null)
	{
		//var_dump('get url ' . $sRoute);
		return $this->getRouteUrl($sRoute, $aParams, $bAbsolute, $sLng);
	}

	/**
	 * Load a static file, optionally using a CDN
	 *
	 * @param string $sFile
	 */
	public function getStatic($sFile)
	{
		return Asap_View_Generic::getStaticRoot() . $sFile;
	}

	/**
	 * Get the available languages
	 */
	public final function getAvailLanguages()
	{
		return $this->_i18n['languages'];
	}

	/**
	 * Get the current language
	 */
	public final function getCurrentLanguage()
	{
		return $this->_currentLng;
	}

	/**
	 * Get the www host for the given language
	 *
	 * @param $sLng
	 */
	public function getHostForLanguage($sLng = null)
	{
		if (empty($sLng) || !isset($this->_i18n['host']))
			return $this->getWebRoot();

		if ($this->_i18n['host'] === false)
			return $this->_controller->getHostForLanguage($sLng);

		return str_replace('{language}', $sLng, $this->_i18n['host']);
	}

	/**
	 * Get the alternative languages for the current page
	 */
	public final function getAlternativeLanguages()
	{
		static $aRet = null;
		if (empty($aRet))
		{
			$aRet = array();
			$aLng = $this->getAvailLanguages();
			$oRoute = $this->_route;
			$sRoute = $oRoute->getName();
			$aParams = $oRoute->getParams();
			//getRouteUrl
			foreach ($aLng as $sLng)
				$aRet[$sLng] = $this->getRouteUrl($sRoute, $aParams, false, $sLng);
		}
		return $aRet;
	}








	/**
	 * Load all the ASAP classes in an optimized way
	 */
	protected function _loadClasses()
	{
		if (ASAP_USE_CLASSES_CACHE && !self::getPHPCache('__asap_classes.inc.php'))
			self::createPHPCache('__asap_classes.inc.php', array(
				ASAP_MAIN_DIR . 'Core/Controller.php',
				ASAP_MAIN_DIR . 'Core/Route.php',

				ASAP_MAIN_DIR . 'Response/Generic.php',
				ASAP_MAIN_DIR . 'Response/Json.php',
				ASAP_MAIN_DIR . 'Response/Xml.php',

				ASAP_MAIN_DIR . 'Database/DB.php',
				ASAP_MAIN_DIR . 'Database/Model.php',

				ASAP_MAIN_DIR . 'View/Generic.php',
				ASAP_MAIN_DIR . 'View/Html.php',
				ASAP_MAIN_DIR . 'View/Json.php',
				ASAP_MAIN_DIR . 'View/Php.php',
				ASAP_MAIN_DIR . 'View/Twig.php',
				ASAP_MAIN_DIR . 'View/Xml.php',

				ASAP_MAIN_DIR . 'Cache/Generic.php',
				ASAP_MAIN_DIR . 'Cache/APC.php',
				ASAP_MAIN_DIR . 'Cache/Memcached.php',
				ASAP_MAIN_DIR . 'Cache/File.php',
				ASAP_MAIN_DIR . 'Cache/NoCache.php',

				ASAP_MAIN_DIR . 'I18N/Generic.php',
				ASAP_MAIN_DIR . 'I18N/Array.php',
				ASAP_MAIN_DIR . 'I18N/Gettext.php',

				ASAP_MAIN_DIR . 'Util/YamlFile.php',
				ASAP_MAIN_DIR . 'Util/Util.php',
				ASAP_MAIN_DIR . 'Util/Mailer.php',
				ASAP_MAIN_DIR . 'Util/Packager.php',
				ASAP_MAIN_DIR . 'Util/Locale.php',
				ASAP_MAIN_DIR . 'Util/DebugBar.php'
			));
	}

	/**
	 * Cache application classes
	 */
	protected function _loadAppClasses()
	{
		$aInfo = $this->_conf->get('asap.app_classes_cache');
		if (empty($aInfo['model']) && empty($aInfo['controller']) && empty($aInfo['lib']))
			return;

		if (!empty($aInfo['lib']) && is_array($aInfo['lib']) && !self::getPHPCache('__app_lib_classes.inc.php'))
			self::createPHPCache('__app_lib_classes.inc.php', ASAP_DIR_LIB);

		if (!empty($aInfo['model']) && !self::getPHPCache('__app_model_classes.inc.php'))
			self::createPHPCache('__app_model_classes.inc.php', ASAP_DIR_MODEL);

		if (!empty($aInfo['controller']) && !self::getPHPCache('__app_controller_classes.inc.php'))
			self::createPHPCache('__app_controller_classes.inc.php', ASAP_DIR_CONTROLLER);
	}



	/**
	 * Load corresponding controller and execute corresponding action
	 */
	protected function _launchAction()
	{
		//var_dump($this->_route);
		//die();

		if (!empty($_SERVER['ASAP_FORCE_CONTROLLER']))
			$this->_route->setController($_SERVER['ASAP_FORCE_CONTROLLER']);

		$sController = ucfirst($this->_route->getController()) . 'Controller';
		$sControllerFile = ASAP_DIR_CONTROLLER . $sController . '.php';
		if (!file_exists($sControllerFile))
			return $this->throw404Error('Controller ' . (ASAP_APP ? ASAP_APP . '::' : '') . $sController . ' does not exist');

		//require_once(ASAP_MAIN_DIR . 'Core/Controller/Controller.php');
		if (!class_exists($sController, false))
			require_once($sControllerFile);
		$this->_controller = new $sController();
		$sAction = $this->_route->getAction() . 'Action';

		if (!($this->_controller instanceof Asap_Core_Controller))
			return $this->throw404Error('Controller ' . $sController . ' must extend Asap_Core_Controller');
		//if (!method_exists($this->_controller, $sAction))
		//	return $this->throw404Error('Action ' . $sAction . ' not found in controller ' . $sController);

		// Actually perform the action
		$this->_controller->performActionGeneric($this->_route);
	}

	public function launchAction($sCont, $sAction, array $aParams = array())
	{
		if (empty($_SERVER['REMOTE_ADDR']))
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		$this->_route = new Asap_Core_Route('tmp_custom', empty($_SERVER['REDIRECT_URL']) ? '' : $_SERVER['REDIRECT_URL'], array('action' => $sAction, 'controller' => $sCont, 'cache' => '', 'params' => array_keys($aParams)), array_values($aParams));
		$this->_launchAction();
	}

	/**
	 * Check the route
	 */
	protected function _matchRoute()
	{
		// Set the URL
		$sUrl = (empty($_SERVER['REDIRECT_URL']) ? '/' : $_SERVER['REDIRECT_URL']);//$_SERVER['REQUEST_URI'];
		if (empty($sUrl))
			$sUrl = '/';

		//var_dump($this->_routes->getData());

		$oRoute = $this->_findRouteFor($sUrl);
		if ($oRoute == null)
			return $this->throw404Error('Route not found for URL : ' . $sUrl);

		$this->_route = $oRoute;
	}

	/**
	 * Try to find a route for the given URL
	 *
	 * @param string $sUrl
	 */
	protected function _findRouteFor($sUrl)
	{
		// Just in case the REDIRECT_URL contains the query string, we remove it
		if ($iPos = strpos($sUrl, '?'))
			$sUrl = substr($sUrl, 0, $iPos);

		$aRoutes = $this->_routes->getData();
		foreach ($aRoutes as $sKey => &$aVal)
		{
			if (($aParams = Asap_Core_Route::isMatching($sUrl, $aVal['regexp'])) !== false)
				return new Asap_Core_Route($sKey, $sUrl, $aVal, $aParams);
			foreach ($aVal['i18n'] as $sLng => $aTmp)
				if (($aParams = Asap_Core_Route::isMatching($sUrl, $aTmp['regexp'])) !== false)
					return new Asap_Core_Route($sKey, $sUrl, $aVal, $aParams);
		}

		// No user-defined route is existing, try to generate a default route
		$aParts = explode('/', $sUrl);
		$iCount = count($aParts);
		if ($iCount != 3 && $iCount != 2)
			return null;

		array_shift($aParts);
		$sController = 'main';
		$sAction = '';
		if ($iCount == 2)
			$sAction = $aParts[0];
		else
		{
			$sController = $aParts[0];
			$sAction = $aParts[1];
		}
		if (empty($sAction))
			$sAction = 'index';
		return new Asap_Core_Route($sController . '/' . $sAction, $sUrl, array('url' => $sUrl, 'cache' => false, 'controller' => $sController, 'action' => $sAction, 'regexp' => '', 'params' => array()), array());
	}

	/**
	 * Load the routes
	 */
	protected function _loadRoutes()
	{
		$oCache = Asap_Cache_Generic::getInstance();
		$sCacheKey = 'asap_routes';
		if ($oCache->has($sCacheKey))
		{
			$aData = $oCache->get($sCacheKey);
			$this->_routes = new Asap_Util_YamlFile($aData);
		}
		else
		{
			// Load ASAP routes
			$this->_routes = new Asap_Util_YamlFile(ASAP_MAIN_DIR . 'config/routes.yml');
			// Load app routes that may override ASAP routes
			$this->_routes->merge(ASAP_DIR_CONFIG . 'routes.yml');


			// Load custom routes URLs for languages
			$aRoutesLngs = array();
			$aFiles = scandir(ASAP_DIR_CONFIG);
			foreach ($aFiles as $sFile)
			{
				if ($sFile == '.' || $sFile == '..')
					continue;
				$aMatches = array();
				if (preg_match('/^routes_([a-z]{2})\.yml$/', $sFile, $aMatches))
				{
					$aData = Asap_Util_YamlFile::arrayFromFile(ASAP_DIR_CONFIG . $sFile);
					$aRoutesLngs[$aMatches[1]] = $aData;
				}
			}


			$sDefaultController = $this->_conf->get('asap.controller.default');
			$aData = &$this->_routes->getData();
			//var_dump('*** ROUTES 1 ***');
			//var_dump($aData);
			foreach ($aData as $sKey => &$mRoute)
			{
				// Ensure we get an array
				if (is_string($mRoute))
					$mRoute = array('url' => $mRoute);

				// Load optional I18N URLs
				$mRoute['i18n'] = array();
				foreach ($aRoutesLngs as $sLng => $aLngData)
				{
					// Keep only strings (internationalized URLs)
					if (isset($aLngData[$sKey]) && is_string($aLngData[$sKey]))
					{
						$sTmpURL = $aLngData[$sKey];
						$mRoute['i18n'][$sLng] = array('regexp' => Asap_Core_Route::toRegexp($sTmpURL, $mRoute), 'url' => $sTmpURL);
					}
				}

				// Cache for template
				if (!isset($mRoute['cache']) || (empty($mRoute['cache']) && $mRoute['cache'] !== 0) || $mRoute['cache'] == -1)
					$mRoute['cache'] = false;

				// Controller and Action
				if (empty($mRoute['action']))
				{
					$mRoute['controller'] = $sDefaultController;
					$mRoute['action'] = $sKey;
				}
				else
				{
					if (strpos($mRoute['action'], '.') === false)
					{
						$sController = $sDefaultController;//throw new Exception('Bad action for route : ' . $sKey);
						$sAction = $mRoute['action'];
					}
					else
						list($sController, $sAction) = explode('.', $mRoute['action']);
					$mRoute['controller'] = $sController;
					$mRoute['action'] = $sAction;
				}

				if ($mRoute['url'][0] != '/')
					$mRoute['url'] = '/' . $mRoute['url'];

				// Misc
				$mRoute['regexp'] = Asap_Core_Route::toRegexp($mRoute['url'], $mRoute);
				$mRoute['params'] = Asap_Core_Route::extractParams($mRoute['url']);
			}

			//$aData = $this->_routes->getData();
			//var_dump('*** ROUTES 2 ***');
			//var_dump($aData);

			$oCache->set($sCacheKey, $aData);
		}
	}

	/**
	 * Load the databases
	 */
	protected function _loadDatabases()
	{
		$this->_databases = array();
		$aDatabases = $this->_conf->get('asap.databases');
		foreach ($aDatabases as $sId => $aDB)
		{
			//var_dump($aDB);
			$this->_databases[$sId] = array('info' => array_merge($aDB, array('id' => $sId)), 'pdo' => null);
		}
	}


	/**
	 * Remove the configuration cache
	 */
	protected function _clearConfCache()
	{
		Asap_View_Generic::clearConfCache();
	}

	/**
	 * Load the configuration (no cache)
	 */
	public static function loadConf($sEnv = null)
	{
		static $oConf = null;
		if ($oConf === null)
		{
			if (empty($sEnv))
				$sEnv = getenv('ASAP_ENV');
			$oConf = new Asap_Util_YamlFile(ASAP_MAIN_DIR . 'config/asap.yml');
			$oConf->merge(ASAP_DIR_CONFIG . 'app.yml');
			$oConf->merge(ASAP_DIR_CONFIG . 'app_' . $sEnv . '.yml');
			$oConf->set('asap.application.environment', $sEnv);
		}
		return $oConf;
	}

	/**
	 * Load the configuration
	 */
	protected function _loadConf()
	{
		// First, the JSON intermediate cache file
		$sEnv = getenv('ASAP_ENV');
		$this->_conf = null;
		$this->_environment = $sEnv;
		$sCacheKey = 'asap_config_' . $sEnv;
		$sCacheFile = ASAP_DIR_CACHE . '__app_config_' . $sEnv . '.json';
		if ($sEnv == 'dev' || !file_exists($sCacheFile))
		{
			// Generate intermediate JSON cache file
			$this->_conf = self::loadConf($sEnv);
			$aConfData = $this->_conf->getData();
			file_put_contents($sCacheFile, json_encode($aConfData));

			// Save it to memory cache if necessary
			if ($this->isUseCache())
			{
				$oCache = Asap_Cache_Generic::getInstance();
				$oCache->set($sCacheKey, $aConfData);
				$this->_clearConfCache();
			}
		}
		else
		{
			// The JSON file already exists. Now we must not load it unless we don't already have the conf in memory cache
			$oCache = Asap_Cache_Generic::getInstance();
			// We don't have the conf in memory cache, write it
			$sJSONData = file_get_contents($sCacheFile);
			$this->_conf = Asap_Util_YamlFile::factoryJSON($sJSONData);
		}

		// Just make sure the cache instance is properly initialized now that the conf is assured to be fully loaded
		$oCache = Asap_Cache_Generic::getInstance();

		$this->_loadDatabases();
	}




	/**
	 * Shortcut method to get data from the cache, and set it using callback/closure if not existing
	 *
	 * @param $sKey
	 * @param $aCallback
	 */
	public static function getSetCache($sKey, $mCallback, $aCallbackParams = array(), $iTTL = 0)
	{
		$oCache = $GLOBALS['asap_cache'];
		return $oCache->getSet($sKey, $mCallback, $aCallbackParams, $iTTL);
	}


	/**
	 * Throws an exception or generates a 404 error page depending on debug mode
	 *
	 * @param $sMsg
	 */
	public function throw404Error($sMsg = '', $bRedirect = false)
	{
		if ($this->isDebug())
			throw new Exception('ERROR 404 : ' . $sMsg);
		else
		{
			if ($bRedirect)
				Asap_Core_Controller::redirect404();
			else
			{
				if (empty($this->_controller))
				{
					if (!class_exists('MainController'))
						require_once(ASAP_DIR_CONTROLLER . 'MainController.php');
					$this->_controller = new MainController();
				}

				file_put_contents(ASAP_DIR_LOG . '404.log', '[' . date('Y-m-d H:i:s') . '] [' . Asap_Util_Util::getIP() . '] ' . $_SERVER['REDIRECT_URL'] . "\r\nFrom : " . (empty($_SERVER['HTTP_REFERER']) ? 'n/a' : $_SERVER['HTTP_REFERER']) . "\r\n", FILE_APPEND);

				//if (empty($this->_route) || !is_object($this->_route))

				header('HTTP/1.1 404 Not Found');
				$this->_route = new Asap_Core_Route('tmp_asap_404', $_SERVER['REDIRECT_URL'], array('action' => 'custom404', 'controller' => 'main', 'cache' => '', 'params' => array()), array('message_404' => $sMsg));
				$this->_controller->performActionGeneric($this->_route);
				die();
			}
		}
	}

	/**
	 * Throws an exception or generates a 500 error page depending on debug mode
	 *
	 * @param $sMsg
	 */
	public function throw500Error($sMsg = '')
	{
		if ($this->isDebug())
			throw new Exception('ERROR 500 : ' . $sMsg);
		else
		{
			if (empty($this->_controller))
			{
				if (!class_exists('MainController'))
					require_once(ASAP_DIR_CONTROLLER . 'MainController.php');
				$this->_controller = new MainController();
			}

			file_put_contents(ASAP_DIR_LOG . '500.log', '[' . date('Y-m-d H:i:s') . '] [' . Asap_Util_Util::getIP() . '] ' . $_SERVER['REDIRECT_URL'] . "\r\nFrom : " . (empty($_SERVER['HTTP_REFERER']) ? 'n/a' : $_SERVER['HTTP_REFERER']) . "\r\n", FILE_APPEND);

			//if (empty($this->_route) || !is_object($this->_route))

			header('HTTP/1.1 500 Internal Server Error');
			$this->_route = new Asap_Core_Route('tmp_asap_500', $_SERVER['REDIRECT_URL'], array('action' => 'custom500', 'controller' => 'main', 'cache' => '', 'params' => array()), array('message_500' => $sMsg));
			$this->_controller->performActionGeneric($this->_route);
			die();
		}
	}

	/**
	 * Throws an exception or generates a 500 error page depending on debug mode
	 *
	 * @param $sMsg
	 */
	public function throw403Error($sReason = '', $aOtherParams = array())
	{
		if ($this->isDebug())
			throw new Exception('ERROR 403 : ' . $sReason);
		else
		{
			if (empty($this->_controller))
			{
				if (!class_exists('MainController'))
					require_once(ASAP_DIR_CONTROLLER . 'MainController.php');
				$this->_controller = new MainController();
			}

			$sURL = (empty($_SERVER['REDIRECT_URL']) ? $_SERVER['REQUEST_URI'] : $_SERVER['REDIRECT_URL']);

			file_put_contents(ASAP_DIR_LOG . '403.log', '[' . date('Y-m-d H:i:s') . '] [' . Asap_Util_Util::getIP() . '] ' . $sURL . "\r\nFrom : " . (empty($_SERVER['HTTP_REFERER']) ? 'n/a' : $_SERVER['HTTP_REFERER']) . "\r\nReason : " . $sReason . "\r\n", FILE_APPEND);

			header('HTTP/1.1 403 Forbidden');
			$aOtherParams['message_403'] = $sReason;
			$this->_route = new Asap_Core_Route('tmp_asap_403', $sURL, array('action' => 'custom403', 'controller' => 'main', 'cache' => '', 'params' => array()), array());
			$this->_controller->performActionGeneric($this->_route, $aOtherParams);
			die();
		}
	}








	/**
	 * Autoloader for Asap
	 *
	 * @param string $sClass The class to be loaded
	 */
	public static function autoload_asap($sClass)
	{
		if (strpos($sClass, 'Asap') !== 0)
			return false;

		$sPath = ASAP_DIR_VENDOR . str_replace('_', '/', $sClass) . '.php';
		if (file_exists($sPath))
		{
			require_once($sPath);
			return true;
		}

		//var_dump(ASAP_DIR_VENDOR);
		//throw new Exception('Class ' . $sClass . ' not found in ASAP !');
		return false;
	}

	/**
	 * Autoloader for app (MVC)
	 *
	 * @param string $sClass The class to be loaded
	 */
	public static function autoload_app($sClass)
	{
		static $aList = array(ASAP_DIR_MODEL, ASAP_DIR_CONTROLLER, ASAP_DIR_LIB);

		foreach ($aList as $sPath)
		{
			$sFile = $sPath . $sClass . '.php';
			if (file_exists($sFile))
			{
				require_once($sFile);
				return true;
			}
		}

		return false;
		/*$sPath = ASAP_DIR_VENDOR . str_replace('_', '/', $sClass) . '.php';
		if (file_exists($sPath))
		{
			require_once($sPath);
			return true;
		}*/

		//return false;
		//throw new Exception('Class ' . $sClass . ' not found in ASAP !');
	}







	public function __clone()
	{
		throw new Exception('Clone is forbidden');
	}

	public static function getInstance()
	{
		//throw new Exception('interdit !!!');
		return self::$_instance;
	}









	/**
	 * Set the error handler
	 */
	protected function _setErrorHandler()
	{
		if (!$this->isDebug())
			set_error_handler('Asap_Core_Asap::error_handler_prod');
		else
			set_error_handler('Asap_Core_Asap::error_handler_debug');

		//set_error_handler('Asap_Core_Asap::error_handler_debug');
	}

	/**
	 * Error handler
	 *
	 * @param $errno
	 * @param $errstr
	 * @param $errfile
	 * @param $errline
	 * @param $errcontext
	 */
	public static function error_handler_prod($errno, $errstr, $errfile, $errline, array $errcontext)
	{
		if ($errstr == 'mkdir(): File exists')
			return;

		// We do not display fsockopen errors for proxy detection
		$sFSock = 'fsockopen(): unable to connect to ' . Asap_Util_Util::getIP() . ':80';
		if (!strncmp($errstr, $sFSock, strlen($sFSock)))
			return;

		$sErrno = self::getErrorHandlerCode($errno);
		$aParams = array('error_level' => $sErrno, 'error_msg' => $errstr, 'error_file' => $errfile, 'error_line' => $errline, 'backtrace' => debug_backtrace());
		file_put_contents(ASAP_DIR_LOG . 'errors.log', '[' . date('Y-m-d H:i:s') . '] [' . $sErrno . '] [' . Asap_Util_Util::getIP() . '] ' . $errstr . ' // ' . $errfile . ' // ' . $errline . ' // ' . (empty($_SERVER['REDIRECT_URL']) ? 'no_redirect_url' : $_SERVER['REDIRECT_URL']) . "\r\n" . json_encode(debug_backtrace()) . "\r\n\r\n", FILE_APPEND);

		if ($errno != 'E_ERROR')
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		else
		{
			Asap_Core_Controller::setFlash('error_occured', 'An error occured, our team has been advised, please excuse us.');
			Asap_Core_Controller::redirect('');
		}

		//$oView = new Asap_View_Twig();
		//$oView->render('asap_error.html.twig', $aParams);
		/*var_dump($errno);
		var_dump($errstr);
		var_dump($errfile);
		var_dump($errline);
		var_dump($errcontext);*/
		//die('');
	}

	/**
	 * Error handler
	 *
	 * @param $errno
	 * @param $errstr
	 * @param $errfile
	 * @param $errline
	 * @param $errcontext
	 */
	public static function error_handler_debug($errno, $errstr, $errfile, $errline, array $errcontext)
	{
		$sErrno = self::getErrorHandlerCode($errno);
		$aParams = array('error_level' => $sErrno, 'error_msg' => $errstr, 'error_file' => $errfile, 'error_line' => $errline, 'backtrace' => debug_backtrace());
		if ($errno != 'E_ERROR')
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		else
		{
			var_dump($aParams);
			die();
		}
		//file_put_contents(ASAP_DIR_LOG . 'errors.log', '[' . date('Y-m-d H:i:s') . '] [ERROR] [' . Asap_Util_Util::getIP() . '] Mauvaise action ' . $sMethod . ' pour URL ' . $_SERVER['REDIRECT_URL'] . "\r\n", FILE_APPEND);

		$oView = new Asap_View_Twig();
		$oView->render('asap_error.html.twig', $aParams);
		/*var_dump($errno);
		var_dump($errstr);
		var_dump($errfile);
		var_dump($errline);
		var_dump($errcontext);*/
		die('');
	}

	protected static function getErrorHandlerCode($errno)
	{
		switch ($errno)
		{
			case E_ERROR:
				$errno = 'E_ERROR';
				break;
			case E_WARNING:
				$errno = 'E_WARNING';
				break;
			case E_NOTICE:
				$errno = 'E_NOTICE';
				break;
			case E_USER_NOTICE:
				$errno = 'E_USER_NOTICE';
				break;
			case E_USER_WARNING:
				$errno = 'E_USER_WARNING';
				break;
			case E_USER_ERROR:
				$errno = 'E_USER_ERROR';
				break;
			case E_STRICT:
				$errno = 'E_STRICT';
				break;
			default:
				$errno = 'UNKNOWN (' . $errno .')';
		}
		return $errno;
	}

	public static function getPHPCache($sKey)
	{
		$sCache = ASAP_DIR_CACHE . $sKey;
		if (!file_exists($sCache))
			return false;

		require_once($sCache);
		return true;
	}

	public static function createPHPCache($sKey, $mFilesOrPath, $sExt = '.php')
	{
		if (is_string($mFilesOrPath))
		{
			$aAll = array();
			$bApp = false;
			if ($mFilesOrPath == ASAP_DIR_CONTROLLER)
			{
				$aAll[] = $mFilesOrPath . 'Application.php';
				$bApp = true;
			}
			$aFiles = scandir($mFilesOrPath);
			foreach ($aFiles as $sFile)
				if ($sFile != '.' && $sFile != '..' && ($sExt == '' || substr($sFile, -4) == $sExt) && (!$bApp || $sFile != 'Application.php'))
					$aAll[] = $mFilesOrPath . $sFile;
		}
		else
			$aAll = $mFilesOrPath;

		$sCache = ASAP_DIR_CACHE . $sKey;
		if (file_exists($sCache))
			unlink($sCache);
		foreach ($aAll as $sFile)
		{
			$sContents = file_get_contents($sFile);
			self::removePHPEndTag($sContents);
			file_put_contents($sCache, $sContents . "\r\n?>\r\n", FILE_APPEND);
		}

		self::getPHPCache($sKey);
	}

	protected static function removePHPEndTag(&$sContents)
	{
		$sContents = rtrim($sContents);
		if (substr($sContents, -2) == '?>')
			$sContents = substr($sContents, 0, -2);
	}
}

