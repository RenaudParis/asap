<?php
/**
 * @package Asap/Core
 */

/**
 * Represents a route
 *
 * @package Asap/Core
 * @author Lideln
 */
class Asap_Core_Route
{
	const PARAM_REGEXP = '/\{([^\}]*)\}/';


	protected $_key;
	protected $_url;
	protected $_route;
	protected $_params;


	/**
	 * Create a route
	 *
	 * @param string $sUrl
	 * @param array $aRoute
	 * @param array $aParams
	 */
	public function __construct($sKey, $sUrl, array $aRoute, array $aParams)
	{
		$this->_key = $sKey;
		$this->_url = $sUrl;
		$this->_route = $aRoute;
		$this->_params = array();
		foreach ($this->_route['params'] as $iIdx => $sParam)
			$this->_params[$sParam] = (isset($aParams[$iIdx]) ? $aParams[$iIdx] : null);
	}



	/**
	 * Get the route name
	 */
	public function getName()
	{
		return $this->_key;
	}

	/**
	 * Get the matching URL
	 *
	 * @return string
	 */
	public function getUrl()
	{
		return $this->_url;
	}

	/**
	 * Get this route controller
	 *
	 * @return string
	 */
	public function getController()
	{
		return $this->_route['controller'];
	}

	public function setController($sController)
	{
		$this->_route['controller'] = $sController;
	}

	/**
	 * Get this route action
	 *
	 * @return string
	 */
	public function getAction()
	{
		return $this->_route['action'];
	}

	/**
	 * Get this route params
	 *
	 * @return array
	 */
	public function getParams()
	{
		return $this->_params;
	}

	/**
	 * Get the route param
	 *
	 * @param string $sParam
	 */
	public function getParam($sParam)
	{
		return (isset($this->_params[$sParam]) ? $this->_params[$sParam] : null);
	}

	/**
	 * Check if the current route has the given param
	 *
	 * @param string $sParam The param name
	 * @param bool $bMustBeNotEmpty Must the param not be empty also ?
	 */
	public function hasParam($sParam, $bMustBeNotEmpty = true)
	{
		if (!isset($this->_params[$sParam]))
			return false;
		return ($bMustBeNotEmpty ? !empty($this->_params[$sParam]) : true);
	}

	/**
	 * Get cache TTL
	 */
	public function getCacheTTL()
	{
		return $this->_route['cache'];
	}

	public function getCustom($sKey)
	{
		if (empty($this->_route[$sKey]))
			return null;

		return $this->_route[$sKey];
	}




	/**
	 * Check if the given route regexp matches the given URL
	 *
	 * @param string $sUrl The URL to check
	 * @param string $sRouteRegexp The route generated regexp
	 *
	 * @return bool
	 */
	public static function isMatching($sUrl, $sRouteRegexp)
	{
		$aParams = array();
		if (!preg_match($sRouteRegexp, $sUrl, $aParams))
			return false;

		array_shift($aParams);
		return $aParams;
	}

	/**
	 * Convert a route to a regexp
	 *
	 * @param string $sRoute
	 * @param array $mRouteData
	 *
	 * @return string
	 */
	public static function toRegexp($sRoute, &$mRouteData)
	{
		$sRoute = str_replace('/', '\\/', $sRoute);

		// Optional custom params regexp rules
		if (!empty($mRouteData['params']))
			foreach ($mRouteData['params'] as $sParam => $sRegexp)
				$sRoute = str_replace('{' . $sParam . '}', $sRegexp, $sRoute);

		return '/^' . preg_replace(self::PARAM_REGEXP, '([^\/]*)', $sRoute) . '$/';
	}

	/**
	 * Extract the parameters from the route
	 *
	 * @param string $sRoute
	 *
	 * @return array
	 */
	public static function extractParams($sRoute)
	{
		$aParams = array();
		if (!preg_match_all(self::PARAM_REGEXP, $sRoute, $aParams))
			return array();

		return $aParams[1];
	}
}

