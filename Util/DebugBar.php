<?php
/**
 * @package Asap/Util
 */

/**
 * Used to display debug information in debug mode
 *
 * @package Asap/Util
 * @author Lideln
 */
class Asap_Util_DebugBar
{
	protected static $_instance = null;
	protected $_results = null;


	protected function __construct()
	{
		$this->_results = array();

		// Imports
		$aImports = get_included_files();
		$this->_results['imports'] = array('nb' => count($aImports), 'results' => $aImports);

		// Memory
		$aData = array('current' => $this->_toMo(memory_get_usage()), 'max' => $this->_toMo(memory_get_peak_usage()));
		$this->_results['memory'] = array('nb' => $this->_toMo(memory_get_usage()), 'results' => $aData);

		// Time
		$this->_results['time'] = array('nb' => null, 'results' => null);

		// Database
		$this->_results['database'] = array('nb' => null, 'results' => null);

		$aLog = Asap_Cache_Generic::getLog();
		if ($aLog === null)
		{
			$aLog = array();
			$iNb = '(off)';
		}
		else
		{
			$iNb = 0;
			foreach ($aLog as $sType => $aTypeLog)
				$iNb += count($aTypeLog);
		}
		$this->_results['cache'] = array('nb' => $iNb, 'results' => $aLog);
	}

	/**
	 * Show the debug bar
	 */
	public function show()
	{
		$this->_results['time']['nb'] = round((microtime(true) - Asap_Core_Asap::$fBeginTime) * 1000);

		$aLog = Asap_Database_DB::getLog();
		$this->_results['database'] = array('nb' => count($aLog), 'results' => $aLog);

		$oView = new Asap_View_Twig();
		$aParams = array('debugBar' => $this);
		$oView->render('asap_debug_bar.twig', $aParams);
	}

	/**
	 * Get the amount for the given category
	 *
	 * @param string $sCat
	 */
	public function getNb($sCat)
	{
		if (!isset($this->_results[$sCat]))
			return 'null';
		return $this->_results[$sCat]['nb'];
	}

	/**
	 * Get the info associated to that category
	 *
	 * @param unknown_type $sCat
	 */
	public function getInfo($sCat)
	{
		if (!isset($this->_results[$sCat]))
			return null;
		return $this->_results[$sCat]['results'];
	}

	protected function _toMo($iOctets)
	{
		return round($iOctets / (1024 * 1024), 2);
	}



	/**
	 * Get the DebugBar instance
	 *
	 * @return Asap_Util_DebugBar
	 */
	public static function getInstance()
	{
		if (self::$_instance === null)
			self::$_instance = new Asap_Util_DebugBar();
		return self::$_instance;
	}
}
