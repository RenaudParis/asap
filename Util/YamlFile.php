<?php


/**
 * Represents a Yaml file (basically an array with added functions)
 *
 * @author Lideln
 */
class Asap_Util_YamlFile
{
	/**
	 * Yaml data
	 *
	 * @var array
	 */
	protected $_data;


	/**
	 * Create a new instance
	 *
	 * @param string|array $mFilenameOrArray
	 */
	public function __construct($mFilenameOrArray)
	{
		if (is_array($mFilenameOrArray))
			$this->_data = $mFilenameOrArray;
		else
			$this->_data = self::arrayFromFile($mFilenameOrArray);
	}


	/**
	 * Generate a YAML file instance using JSON data
	 *
	 * @param string $sJSONData
	 */
	public static function factoryJSON(&$sJSONData)
	{
		$aData = json_decode($sJSONData, true);
		return new self($aData);
	}


	/**
	 * Get a value from the Yaml array
	 *
	 * @param string $sMasterKey Key of the value to find
	 */
	public function get($sMasterKey)
	{
		$sVal = $this->_data;
		if (!empty($sMasterKey))
		{
			$aList = explode('.', $sMasterKey);
			foreach ($aList as $sKey)
			{
				// Return null for non existing (or null) values
				if (!is_array($sVal) || !array_key_exists($sKey, $sVal))
				{
					if ($GLOBALS['asap']->isDebug())
						throw new Exception('Problem retrieving value for ' . $sMasterKey);//return null;
					else
						return null;
				}
				$sVal = &$sVal[$sKey];
			}
		}

		return $sVal;
	}

	/**
	 * Set a value
	 *
	 * @param unknown_type $sMasterKey
	 */
	public function set($sMasterKey, $mValue)
	{
		if (!empty($sMasterKey))
		{
			$sVal = &$this->_data;
			$aList = explode('.', $sMasterKey);
			foreach ($aList as $sKey)
			{
				// Return null for non existing (or null) values
				if (!is_array($sVal) || !isset($sVal[$sKey]))
					throw new Exception('Problem retrieving value for ' . $sMasterKey);//return null;
				$sVal = &$sVal[$sKey];
			}
			$sVal = $mValue;
		}
	}

	/**
	 * Merge with another Yaml
	 *
	 * @param string|array|YamlFile $mFileArrayObj A filename, array, or YamlFile instance
	 */
	public function merge($mFileArrayObj)
	{
		//require_once(ASAP_MAIN_DIR . 'Util/Util.php');

		$aData = array();

		if (is_string($mFileArrayObj))
			$aData = self::arrayFromFile($mFileArrayObj);
		else if (is_array($mFileArrayObj))
			$aData = &$mFileArrayObj;
		else if ($mFileArrayObj instanceof YamlFile)
			$aData = $mFileArrayObj->getData();
		else
			throw new Exception('YamlFile::merge : passed argument is not supported');

		Asap_Util_Util::array_merge($this->_data, $aData);
	}

	/**
	 * Get the Yaml data
	 *
	 * @return array
	 */
	public function &getData()
	{
		return $this->_data;
	}




	/**
	 * Get Yaml array from a file
	 *
	 * @param string $sFilename
	 */
	public static function arrayFromFile($sFilename)
	{
		//var_dump($sFilename);
		if (!file_exists($sFilename))
			return array();

		try
		{
			$oParser = self::getYamlParser();
			$sStr = file_get_contents($sFilename);
			$mRes = $oParser->parse($sStr);//sfYaml::load($sFile);
			if (is_null($mRes) || is_string($mRes))
				return array();

			return $mRes;
		}
		catch (InvalidArgumentException $e)
		{
			throw new Exception('YAML file error : ' . $e->getMessage());
		}

		return array();
	}

	/**
	 * Convert an array to YAML string
	 *
	 * @param array $aArr
	 */
	public static function fromArray(array &$aArr)
	{
		require_once(ASAP_DIR_VENDOR . 'sfYaml/sfYamlDumper.php');
		$dumper = new sfYamlDumper();
		return $dumper->dump($aArr, true);
	}

	/**
	 * Get a Yaml Parser instance
	 *
	 * @return sfYamlParser
	 */
	public static function getYamlParser()
	{
		static $oParser = null;
		if ($oParser == null)
		{
			require_once(ASAP_DIR_VENDOR . 'sfYaml/sfYamlParser.php');
			$oParser = new sfYamlParser();
		}
		return $oParser;
	}
}
