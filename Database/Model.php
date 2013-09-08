<?php
/**
 * @package Asap/Database
 */

/**
 * Model base
 *
 * @package Asap/Database
 * @author Lideln
 */
abstract class Asap_Database_Model
{
	protected static $__db = 'main';
	protected static $__pdo = null;
	protected $__table = null;//'__tbd__';
	protected $__pk = 'id';
	protected $__pk_cond;


	public function __construct($mPK = null)
	{
		if (empty($this->__table))
		{
			$sClass = get_called_class();
			$this->__table = self::getTableFromClass($sClass);
		}

		if ($mPK !== null)
			$this->load($mPK);
	}


	/**
	 * Hydrate object with the given data (FIXME : excess keys will be used when saving in DB, which is BAD !!)
	 *
	 * @param array $aData
	 */
	public function loadData_p(&$aData)
	{
		foreach ($aData as $sKey => $sVal)
			$this->$sKey = $sVal;
	}

	/**
	 * Hydrate object with the given data (FIXME : excess keys will be used when saving in DB, which is BAD !!)
	 *
	 * @param array $aData
	 */
	public function loadData($aData)
	{
		foreach ($aData as $sKey => $sVal)
		{
			$sKey = trim($sKey);
			if (empty($sKey))
			{
				/*var_dump('loadData: key is empty !');
				var_dump($sKey);
				var_dump($aData);
				echo '<pre>', print_r(debug_get_backtrace(), true), '</pre>';
				die('fin');
				*/
				continue;
			}
			$this->$sKey = $sVal;
		}
	}

	/**
	 * Hydrate object with the given id
	 *
	 * @param string|int|array $mPK
	 */
	public function load($mPK)
	{
		if (!is_array($mPK))
			$aPK = array($this->__pk => $mPK);
		else
		{
			$aPK = $mPK;
			$mPK = '';
			foreach ($aPK as $sKey => $mValue)
				$mPK .= (empty($mPK) ? '' : '-') . $mValue;
		}
		$this->loadData($aPK);

		$iCacheTTL = -1;
		$sCacheKey = '';
		$this->_loadUseCache($mPK, $iCacheTTL, $sCacheKey);
		$aParams = $this->getPKVals();
		$aData = $this->getDB()->fetchOne('SELECT * FROM ' . $this->__table . ' WHERE ' . $this->getPKCond() . ' LIMIT 1', $aParams, $iCacheTTL, $sCacheKey);
		if ($aData === false)
			return;

		$this->loadData($aData);
	}

	/**
	 * Check if we must use cache for this object (default : no)
	 *
	 * @param mixed $mPK
	 * @param int $iCacheTTL
	 * @param string $sCacheKey
	 */
	protected function _loadUseCache($mPK, &$iCacheTTL, &$sCacheKey)
	{
	}

	/**
	 * Get the object as an array
	 */
	public function getData()
	{
		$aVars = get_object_vars($this);
		foreach ($aVars as $sKey => $mVal)
			if ($sKey[0] == '_')
				unset($aVars[$sKey]);
		return $aVars;
	}

	/**
	 * Save (insert or update) the current object
	 *
	 * @param array $aFields An optional array of fields to save (for optim) (default null, means all fields)
	 */
	public function save($aFields = null, $bReplace = true)
	{
		if (is_string($aFields))
			$aFields = array($aFields);

		$bKeyDefined = $this->isPKDefined();
		if (!empty($aFields) && !$bKeyDefined)
			throw new Exception('Cannot UPDATE with PRIMARY KEY fields not set');

		$aVars = get_object_vars($this);
		foreach ($aVars as $sKey => $mVal)
		{
			$bIsPK = $this->isPK($sKey);
			if ($sKey[0] == '_' || (!$bKeyDefined && $bIsPK) || ($aFields && !$bIsPK && !in_array($sKey, $aFields)))
				unset($aVars[$sKey]);
		}

		$aParams = array();
		$sSql = (is_array($aFields) ? 'UPDATE' : ($bReplace ? 'REPLACE' : 'INSERT IGNORE') . ' INTO');
		$sSql .= ' ' . $this->__table . ' SET ';
		$bFirst = true;
		foreach ($aVars as $sKey => $mVal)
		{
			$sSql .= ($bFirst ? '' : ', ') . '`' . $sKey . '` = :' . $sKey;
			$aParams[':' . $sKey] = $mVal;
			$bFirst = false;
		}

		if (is_array($aFields))
		{
			$sSql .= ' WHERE ' . $this->getPKCond();
			$aParams = array_merge($aParams, $this->getPKVals());
		}

		//var_dump($sSql);
		//var_dump($aParams);

		$this->getDB()->execute($sSql, $aParams);

		//die('good');

		if (!$bKeyDefined && is_string($this->__pk))
		{
			$iInsertId = $this->getDB()->pdo_lastInsertId();
			$this->{$this->__pk} = $iInsertId;
			return $iInsertId;
		}
	}

	/**
	 * Delete the current record
	 */
	public function delete()
	{
		$this->getDB()->execute('DELETE FROM ' . $this->__table . ' WHERE ' . $this->getPKCond() . ' LIMIT 1', $this->getPKVals());
	}













	/**
	 * Create an item and load data
	 *
	 * @param array $aData
	 */
	public static function factoryOne($mPK)
	{
		return new static($mPK);
	}

	/**
	 * Create an item and load data
	 *
	 * @param array $aData
	 */
	public static function factory(&$aData)
	{
		$oObj = new static();
		$oObj->loadData_p($aData);
		return $oObj;
	}

	/**
	 * Batch load data
	 *
	 * @param array $aData
	 */
	public static function loadDataBatch(&$aData)
	{
		$aObjs = array();
		foreach ($aData as &$aRow)
		{
			$oObj = new static();
			$oObj->loadData($aRow);
			$aObjs[] = $oObj;
		}

		return $aObjs;
	}

	/**
	 * Batch load PK
	 *
	 * @param array $aData
	 */
	public static function loadBatch($aData)
	{
		$aObjs = array();
		// FIXME : optimiser ici en faisant une seule requête ! (au lieu de possiblement 10 ou 100 !)
		foreach ($aData as &$mPK)
		{
			$oObj = new static();
			$oObj->load($mPK);
			$aObjs[] = $oObj;
		}

		return $aObjs;
	}

	/**
	 * Get all the items in DB
	 *
	 * @param string $sReq
	 * @param array $aParams
	 * @param int $iCacheTTL = -1
	 * @param string $sCacheKey = ''
	 */
	public static function findAll($sReq = null, $aParams = null, $iCacheTTL = -1, $sCacheKey = '')
	{
		if (empty($sReq))
			$sReq = 'SELECT * FROM ' . self::getTableFromClass(get_called_class());

		// FIXME : use the DB name associated to the given class
		$oDB = $GLOBALS['asap']->getDB();
		$aRows = $oDB->fetchAll($sReq, $aParams, $iCacheTTL, $sCacheKey);
		return self::loadDataBatch($aRows);
	}

	/**
	 * Get one item in DB
	 *
	 * @param $sReq
	 * @param $aParams
	 */
	public static function findOne($sReq = null, $aParams = null, $iCacheTTL = -1, $sCacheKey = '')
	{
		if (empty($sReq))
			$sReq = 'SELECT * FROM ' . self::getTableFromClass(get_called_class()) . ' LIMIT 1';

		// FIXME : use the DB name associated to the given class
		$oDB = $GLOBALS['asap']->getDB();
		$aRow = $oDB->fetchOne($sReq, $aParams, $iCacheTTL, $sCacheKey);
		if (empty($aRow))
			return null;
		return self::factory($aRow);
	}

	/**
	 * Find many items
	 *
	 * @param array $aFieldsVals
	 */
	public static function findAllBy($aFieldsVals, $iCacheTTL = -1, $sCacheKey = '')
	{
		if (empty($aFieldsVals))
			return null;

		$sClass = get_called_class();

		// FIXME : use the DB name associated to the given class
		$oDB = $GLOBALS['asap']->getDB();

		$sReq = 'SELECT * FROM ' . self::getTableFromClass($sClass) . ' WHERE ';
		$aParams = self::computeQueryParams($sReq, $aFieldsVals);

		$aRows = $oDB->fetchAll($sReq, $aParams, $iCacheTTL, $sCacheKey);
		$aObjs = array();
		foreach ($aRows as &$aRow)
		{
			$oObj = new $sClass();
			$oObj->loadData($aRow);
			$aObjs[] = $oObj;
		}
		return $aObjs;
	}

	/**
	 * Find one item
	 *
	 * @param array $aFieldsVals
	 */
	public static function findOneBy($aFieldsVals, $iCacheTTL = -1, $sCacheKey = '')
	{
		if (empty($aFieldsVals))
			return null;

		$sClass = get_called_class();

		// FIXME : use the DB name associated to the given class
		$oDB = $GLOBALS['asap']->getDB();

		$sReq = 'SELECT * FROM ' . self::getTableFromClass($sClass) . ' WHERE ';
		$aParams = self::computeQueryParams($sReq, $aFieldsVals);
		$sReq .= ' LIMIT 1';

		$aRow = $oDB->fetchOne($sReq, $aParams, $iCacheTTL, $sCacheKey);
		if ($aRow === false)
			return null;
		$oObj = new $sClass();
		$oObj->loadData($aRow);
		return $oObj;
	}


	/**
	 * Compute the query parameters
	 *
	 * @param unknown_type $sReq
	 * @param unknown_type $aParams
	 */
	protected static function computeQueryParams(&$sReq, &$aFieldsVals)
	{
		$aParams = array();
		$bFirst = true;
		foreach ($aFieldsVals as $sField => &$mVal)
		{
			if (is_array($mVal) && count($mVal) == 0)
				continue;
			$sKey = ':' . $sField;
			$sReq .= ($bFirst ? '' : ' AND ');
			if (is_array($mVal))
				$sReq .= $sField . ' ' . $oDB->computeINClause($mVal);
			else
			{
				$sReq .= $sField . ' = ' . $sKey;
				$aParams[$sKey] = $mVal;
			}
			if ($bFirst)
				$bFirst = false;
		}
		return $aParams;
	}







	public static function __callStatic($sName, $aArgs)
	{
		if (strpos($sName, 'findOneBy') === 0)
		{
			$sKey = strtolower(str_replace('findOneBy', '', $sName));
			return self::findOneBy(array($sKey => current($aArgs)));
		}
		if (strpos($sName, 'findAllBy') === 0)
		{
			$sKey = strtolower(str_replace('findAllBy', '', $sName));
			return self::findAllBy(array($sKey => current($aArgs)));
		}
		throw new Exception('Static method ' . $sName . ' does not exist in Model.php');
	}

	public function __get($sKey)
	{
		if (!isset($this->$sKey))
			return null; // Prevents issues with null values

		return $this->$sKey;
	}

	public function __set($sKey, $mVal)
	{
		$this->$sKey = $mVal;
	}

	public function __isset($sKey)
	{
		return property_exists($this, $sKey);// || method_exists($this, $sKey);
	}

	public function __toString()
	{
		return '***' . get_class($this) . '***';
	}


	protected static function getDB()
	{
		if (empty(self::$__pdo))
			self::$__pdo = $GLOBALS['asap']->getDB(self::$__db);

		return self::$__pdo;
	}

	public static function setDBType($sType)
	{
		self::$__db = $sType;
		self::$__pdo = null;
	}

	protected function isPK($sKey)
	{
		if (is_string($this->__pk))
			return $this->__pk == $sKey;

		foreach ($this->__pk as $sPK)
			if ($sPK == $sKey)
				return true;

		return false;
	}

	protected function isPKDefined()
	{
		return is_array($this->__pk) || !empty($this->{$this->__pk});
	}

	protected function getPKVals()
	{
		$aRet = array();
		if (is_string($this->__pk))
			$aRet[':' . $this->__pk] = $this->{$this->__pk};
		else
			foreach ($this->__pk as $sPK)
				$aRet[':' . $sPK] = $this->$sPK;

		return $aRet;
	}

	protected function getPKCond()
	{
		if (empty($this->__pk_cond))
		{
			if (is_string($this->__pk))
				$this->__pk_cond = $this->__pk . ' = :' . $this->__pk;
			else
			{
				$this->__pk_cond = '';
				foreach ($this->__pk as $sPK)
				{
					if ($this->__pk_cond != '')
						$this->__pk_cond .= ' AND ';
					$this->__pk_cond .= $sPK . ($this->$sPK === null ? ' IS NULL' : ' = :' . $sPK);
				}
			}
		}

		return $this->__pk_cond;
	}





	/**
	 * Get a table name from the given model class name
	 *
	 * @param string $sClass
	 */
	public static function getTableFromClass($sClass)
	{
		return strtolower(substr($sClass, 0, -5));
	}

	/**
	 * Get a model class name from the given table name
	 *
	 * @param string $sTable
	 */
	public static function getClassFromTable($sTable)
	{
		if (strpos($sTable, '_') !== false)
			return str_replace(' ', '_', ucwords(str_replace('_', ' ', $sTable))) . 'Model';

		return ucfirst($sTable) . 'Model';
	}
}
