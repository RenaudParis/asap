<?php
/**
 * @package Asap/Core
 */

/**
 * Controller main class
 *
 * @package Asap/Core
 * @author Lideln
 */
abstract class Asap_Core_Controller
{
	/**
	 * The Asap instance
	 *
	 * @var Asap
	 */
	protected $_asap;

	/**
	 * The current route
	 *
	 * @var Asap_Core_Route
	 */
	protected $_route;

	/**
	 * Instance
	 *
	 * @var Asap_Core_Controller
	 */
	protected static $_instance = null;

	/**
	 * Used for fragment/page caching
	 */
	protected $_fragment_cache_key;
	protected $_fragment_cache_ttl;

	protected $_view = null;




	/**
	 * Make it final so that we don't lose the _asap pointer
	 * (we could set it using a setter, but I prefer this way)
	 */
	final public function __construct()
	{
		$this->_asap = $GLOBALS['asap'];//Asap_Core_Asap::getInstance();
		self::$_instance = $this;
		$GLOBALS['asap_controller'] = $this;
	}




	public function getViewGlobals()
	{
		return array();
	}



	public function isAjax()
	{
		static $bIsAjax = null;
		if ($bIsAjax === null)
			$bIsAjax = ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
						|| !empty($_REQUEST['_ajax_']));
		return $bIsAjax;
	}



	/**
	 * Get the instance
	 */
	public static final function getInstance()
	{
		return self::$_instance;
	}

	/**
	 * Redirect to 404 Page Not Found
	 */
	public static function redirect404()
	{
		//throw new Exception('redirect 404 !');
		header('HTTP/1.1 404 Not Found');
		header('Status: 404 Not Found');
		header('Location: /errors/not-found.php');
		die();
	}

	/**
	 * Redirect to 401 Page Not Authorized
	 */
	public static function redirect401()
	{
		header('HTTP/1.1 401 Not Authorized');
		header('Status: 401 Not Authorized');
		header('Location: /errors/forbidden.php');
		die();
	}

	/**
	 * Redirect to 403 Page Not Authorized
	 */
	public static function redirect403($sReason = 'vous avez été banni(e) du site')
	{
		self::setFlash('forbidden-reason', $sReason);
		header('HTTP/1.1 403 Forbidden');
		header('Status: 403 Forbidden');
		header('Location: /errors/forbidden.php');
		die();
	}

	/**
	 * Redirect to a route
	 *
	 * @param string $sRoute The route name (identifier)
	 * @param array $aParams
	 */
	public static function redirectRoute($sRoute, array $aParams = array())
	{
		self::redirect(self::getRouteUrl($sRoute, $aParams), true);
	}

	/**
	 * Redirect to a page
	 *
	 * @param string $sUrl The url
	 * @param bool $bNoNeedRoot True if the url is in an absolute form
	 */
	public static function redirect($sUrl, $bNoNeedRoot = false, $iStatus = null)
	{
		if (!empty($iStatus))
		{
			switch ($iStatus)
			{
				case 301:
					header('HTTP/1.1 301 Moved Permanently');
					break;
				case 403:
					header('HTTP/1.1 403 Forbidden');
					break;
				case 404:
					header('HTTP/1.1 404 Not Found');
					break;
			}
		}
		if (!$bNoNeedRoot && !empty($sUrl) && $sUrl[0] == '/')
			$sUrl = substr($sUrl, 1);
		$sNewUrl = ($bNoNeedRoot ? '' : $GLOBALS['asap']->getWebRoot()) . $sUrl;
		header('Location: ' . $sNewUrl);
		die();
	}

	/**
	 * Reload current page
	 */
	public static function reload()
	{
		header('Location: ' . $GLOBALS['asap']->getWebRoot() . substr($_SERVER['REQUEST_URI'], 1));
		die();
	}

	/**
	 * Get the current URL
	 */
	public function getCurrentUrl()
	{
		return $GLOBALS['asap']->getWebRoot() . substr($_SERVER['REQUEST_URI'], 1);
	}

	/**
	 * Get the URL matching the given route and route params
	 *
	 * @param string $sRoute
	 * @param array [optional] $aParams
	 */
	public static function getRouteUrl($sRoute, $aParams = array(), $bAbsolute = true, $sLng = null)
	{
		return $GLOBALS['asap']->getUrl($sRoute, $aParams, $bAbsolute, $sLng);
	}

	/**
	 * Get the URL matching the given route and route params
	 *
	 * @param string $sRoute
	 * @param array [optional] $aParams
	 */
	public static function getUrl($sRoute, $aParams = array(), $bAbsolute = true, $sLng = null)
	{
		return $GLOBALS['asap']->getUrl($sRoute, $aParams, $bAbsolute, $sLng);
	}



	/**
	 * Update a flash value in session
	 */
	public static function setFlash($sKey, $sValue)
	{
		if (!isset($_SESSION['flash']))
			$_SESSION['flash'] = array();
		$_SESSION['flash'][$sKey] = $sValue;
	}

	/**
	 * Check if a flash value is in session
	 */
	public static function hasFlash($sKey)
	{
		if (empty($_SESSION['flash']))
			return false;

		return isset($_SESSION['flash'][$sKey]);
	}

	/**
	 * Get a flash value in session
	 */
	public static function getFlash($sKey)
	{
		if (empty($_SESSION['flash'][$sKey]))
			return null;

		return $_SESSION['flash'][$sKey];
	}

	/**
	 * Clear flash values in session
	 */
	public static function clearFlash($mKey = null)
	{
		if ($mKey === null || !isset($_SESSION['flash']))
			$_SESSION['flash'] = array();
		else if (is_array($mKey))
			$_SESSION['flash'] = array_diff_key($_SESSION['flash'], $mKey);
		else
			unset($_SESSION['flash'][$mKey]);
	}

	/**
	 * Get all the flash variables
	 */
	public static function getAllFlash()
	{
		if (!isset($_SESSION['flash']))
			$_SESSION['flash'] = array();
		return $_SESSION['flash'];
	}


	/**
	 * Called before the beginning of a session
	 */
	public static function onBeforeSessionStart()
	{

	}


	public function GET($sKey)
	{
		return (isset($_GET[$sKey]) ? $_GET[$sKey] : null);
	}

	public function SESSION($sKey)
	{
		return (isset($_SESSION[$sKey]) ? $_SESSION[$sKey] : null);
	}

	public function SERVER($sKey)
	{
		return (isset($_SERVER[$sKey]) ? $_SERVER[$sKey] : null);
	}


	/**
	 * Sets the current view object if any
	 *
	 * @param Asap_View_Generic $oView
	 */
	public function setView(Asap_View_Generic $oView)
	{
		$this->_view = $oView;
	}

	/**
	 * Get the current view object if any
	 */
	public function getView()
	{
		return $this->_view;
	}

	/**
	 * Force a render, overriding the normal behavior of returning a response from the controller
	 */
	public function response(Asap_Response_Generic $oResponse)
	{
		echo Asap_View_Generic::factory($this->_route, $oResponse);
		die();
	}


	/**
	 * Perform the given action
	 *
	 * @param Route $oRoute
	 */
	public final function performActionGeneric(Asap_Core_Route &$oRoute, $aParams = array())
	{
		$this->_route = $oRoute;

		// Trim POST parameters
		if (!empty($_POST))
		{
			foreach ($_POST as $sKey => &$mVal)
				if (is_string($mVal))
					$mVal = trim($mVal);
		}
		else
			$_POST = array();

		// Check that the action does exist
		$sAction = $oRoute->getAction();
		$sMethod = $sAction . 'Action';
		/*
		if (!method_exists($this, $sMethod))
		{
			file_put_contents(ASAP_DIR_LOG . 'errors.log', '[' . date('Y-m-d H:i:s') . '] [ERROR] [' . Asap_Util_Util::getIP() . '] Mauvaise action ' . $sMethod . ' pour URL ' . $_SERVER['REDIRECT_URL'] . "\r\n", FILE_APPEND);
			Asap_Core_Asap::getInstance()->throw404Error();//self::redirect404();//die('redirect 2');//self::redirect('');
		}
		*/
		$bExists = method_exists($this, $sMethod);

		// Check if we execute a function before each action
		$this->_onBeforeAction();

		// Execute the action
		$mRet = null;
		if ($bExists)
			$mRet = call_user_func_array(array($this, $sMethod), $oRoute->getParams());
		if (empty($mRet))
			$mRet = array();
		if (is_array($mRet))
		{
			$mRet = Asap_Util_Util::array_merge($aParams, $mRet);
			$mRet = Asap_Util_Util::array_merge($mRet, $_POST);
		}

		// Cache management (only in standard Action return mode : array of view parameters)
		if (!is_array($mRet) || !$this->useFragmentCache($this->getPageCacheKey(), $this->_route->getCacheTTL(), true))
		{
			// Execute the proper view / xml / json
			echo Asap_View_Generic::factory($oRoute, $mRet);
			$this->saveFragmentCache();
		}

		// FIXME : prévoir de pouvoir mélanger HTML et Twig (en gros avoir plusieurs types de vues qui cohabitent)
	}


	/**
	 * Default action for 404 errors
	 */
	public function custom404Action()
	{
		self::redirect('errors/not-found.php');
	}


	public function hydrateHost($sHost)
	{
		return $sHost;
	}


	/**
	 * Try to use and display cache, if active and fresh
	 *
	 * @param string $sKey The cache key
	 * @param int $iTTL Time to live
	 *
	 * @return bool True if cache is active and fresh
	 */
	public function useFragmentCache($sKey, $iTTL, $bCurrentPage = false)
	{
		$this->_fragment_cache_key = null;
		$this->_fragment_cache_ttl = null;

		// Check if cache is enabled (and specifically for this key and ttl)
		if ($iRet = $this->_mustCacheFragment($sKey, $iTTL, $bCurrentPage))
			return false;

		// Cache is enabled for this page/fragment, now let's see if it's fresh or has to be build again
		$oCache = self::getPageFileCache();
		// Not in _mustCacheFragment because the "fresh" thing cannot be cached (only the conf)
		if (!$oCache->isFresh($sKey, $iTTL))
		{
			$this->_fragment_cache_key = $sKey;
			$this->_fragment_cache_ttl = $iTTL;
			// Start caching
			ob_start();
			return false;
		}

		// Cache is enabled and fresh, display it
		echo $oCache->get($sKey);
		return true;
	}

	/**
	 * Save the current fragment cache
	 */
	public function saveFragmentCache()
	{
		// If not caching, return
		if (!ob_get_length() || empty($this->_fragment_cache_key))
			return;

		$sPageContents = ob_get_flush();
		self::getPageFileCache()->set($this->_fragment_cache_key, $sPageContents, $this->_fragment_cache_ttl);

		// Reset values (otherwise cache may be written several times as this is our only verification)
		$this->_fragment_cache_key = null;
		$this->_fragment_cache_ttl = null;
	}

	/**
	 * Check if we should cache the current page/fragment (cache is active and enabled for this route)
	 *
	 * @return bool
	 */
	private final function _mustCacheFragment($sKey, $iTTL, $bCurrentPage = false)
	{
		//$this->_route->getCacheTTL()
		//$this->getPageCacheKey()

		// Use cache to store result, as it won't change (or at least not often)
		return $GLOBALS['asap_cache']->getSet('app.fragment_cache.' . $sKey, array($this, '_cache_mustCacheFragment'), array($sKey, $iTTL, $bCurrentPage));
	}

	public function _cache_mustCacheFragment($sKey, $iTTL, $bCurrentPage = false)
	{
		// First check configuration
		$bConfActive = $this->getConfPageCache();
		if (empty($bConfActive))
			return 1;

		// Try to load the cache (this should always work because previously checked by "getConfPageCache" above)
		$oCache = $this->getPageFileCache();
		if (!$oCache->active())
			//throw new Exception('Cache non actif');
			return 2;

		// Check if route/fragment has a cache
		if ($iTTL === false)
			//throw new Exception('Cache pour route non actif');
			return 3;

		// Then check in application if they have a last-minute word to say about it before using cache
		if ($bCurrentPage == true && !$this->validateUseOfPageCache())
			return 4;

		return 0;
	}

	/**
	 * Last opportunity to disable a normally enabled page cache for the current action
	 *
	 * @return bool True or false, depending of the validation (or not) of the page cache for the given page
	 */
	protected function validateUseOfPageCache()
	{
		return true;
	}

	/**
	 * Get the page file cache lib
	 */
	public static function getPageFileCache()
	{
		if (empty($GLOBALS['asap_cache_file']))
		{
			$bConfActive = self::getConfPageCache();
			$GLOBALS['asap_cache_file'] = ($bConfActive ? new Asap_Cache_File() : new Asap_Cache_NoCache());
		}
		return $GLOBALS['asap_cache_file'];
	}

	/**
	 * Get the page cache key
	 */
	protected function getPageCacheKey()
	{
		return 'asap_page_' . $this->_asap->getCurrentLanguage() . '_' . str_replace('/', '_', $this->_route->getUrl()) . '.html';
	}

	/**
	 * Get the value in conf for the page/fragment cache
	 */
	protected static function getConfPageCache()
	{
		return $GLOBALS['asap_cache']->getSet('asap.conf.page_cache', function()
		{
			return $GLOBALS['asap']->getParameter('view.cache', 'asap');
		});
	}







	/**
	 * Check if we execute a function before each action in the current controller
	 */
	private final function _onBeforeAction()
	{
		$sFunc = $GLOBALS['asap_cache']->getSet('asap.controller.before_action', function()
		{
			return $GLOBALS['asap']->getParameter('controller.before_action', 'asap');
		});
		if (!empty($sFunc))
			return $this->$sFunc();
	}

	/**
	 * Get the Asap instance
	 */
	public final function getAsap()
	{
		return $this->_asap;
	}

	/**
	 * Get the route
	 */
	public final function getRoute()
	{
		return $this->_route;
	}

	/**
	 * Check if we are displaying this route
	 *
	 * @param string $sTestRoute
	 */
	public final function isRoute($sTestRoute)
	{
		return $this->_route->getName() == $sTestRoute;
	}

	/**
	 * Default action written here for installation purposes
	 */
	public function homepageAction()
	{
		// Generate password protection
		$sPass = sha1(time());
		file_put_contents(ASAP_DIR_WEB . 'asap/__password.txt', $sPass);

		$this->redirect('asap/install.php?password=' . $sPass, true);
	}

	/**
	 * Default empty init function (called before each action)
	 */
	public function init() {}
}
