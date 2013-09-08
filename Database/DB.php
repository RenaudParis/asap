<?php
/**
 * @package Asap/Database
 */

/**
 * Database management class
 *
 * @package Asap/Database
 * @author Lideln
 */
class Asap_Database_DB
{
	const MYSQL_ATTR_INIT_COMMAND = 1002;


	protected $pdo = null;
	protected $stmt = null;

	protected static $_log = null;
	protected static $_debug = false;
	protected static $_startTime = null;
	protected static $_queryStartTime = null;

	protected	$id,
				$driver,
				$host,
				$port,
				$db,
				$user,
				$pass;


	public function __construct($aInfo)
	{
		$this->id = $aInfo['id'];
		$this->driver = $aInfo['driver'];
		$this->host = $aInfo['host'];
		$this->port = $aInfo['port'];
		$this->db = $aInfo['db'];
		$this->user = $aInfo['user'];
		$this->pass = $aInfo['password'];
		$this->charset = $aInfo['charset'];

		if (isset($aInfo['debug']))
			self::$_debug = $aInfo['debug'];
		if (self::$_log === null)
		{
			self::$_log = array();
			self::$_startTime = microtime(true);
		}

		$dsn = $this->driver . ':dbname=' . $this->db . ';host=' . $this->host;
		if (!empty($this->port))
			$dsn .= ';port=' . $this->port;

		$aData = array();
		if ($this->driver == 'mysql' && !empty($this->charset))
			$aData[self::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $this->charset;

		$this->pdo = new PDO($dsn, $this->user, $this->pass, $aData);
	}

	public function pdo_lastInsertId()
	{
		return $this->pdo->lastInsertId();
	}

	public function pdo_query($sQuery, $iFetchMode = PDO::FETCH_ASSOC, stdClass $oObj = null)
	{
		// FIXME : cache !
		self::_beginLogQuery();
		if ($iFetchMode == PDO::FETCH_INTO)
			$mRes = $this->pdo->query($sQuery, $iFetchMode, $oObj);
		else
			$mRes = $this->pdo->query($sQuery, $iFetchMode);
		self::_logQuery($sQuery);
		if ($mRes === false)
			self::onError($this->pdo);

		return $mRes;
	}

	public function pdo_quote($mVal)
	{
		return $this->pdo->quote($mVal);
	}

	public function pdo_prepare($sQuery)
	{
		// FIXME : cache !?
		$this->stmt = $this->pdo->prepare($sQuery);
		if ($this->stmt === false)
			self::onError($this->pdo);

		return $this->stmt;
	}

	public function pdo_execute($aParams, PDOStatement $oStmt = null)
	{
		// FIXME : cache !?
		if (empty($oStmt))
			$oStmt = $this->stmt;
		if (!is_null($aParams) && !is_array($aParams))
			$aParams = array($aParams);
		try
		{
			$bRes = $oStmt->execute($aParams);
		}
		catch (PDOException $e)
		{
			file_put_contents(ASAP_DIR_LOG . 'db_errors_' . date('Y-m-d') . '.log', '*** ' . date('Y-m-d H:i:s') . ' ***' . "\r\n" . $e->getMessage() . "\r\n" . json_encode(debug_backtrace()) . "\r\n\r\n", FILE_APPEND);
		}
		if ($bRes === false)
			self::onError($oStmt);

		return $oStmt;
	}

	public function pdo_fetch($iMode = PDO::FETCH_ASSOC, PDOStatement $oStmt = null)
	{
		if (empty($oStmt))
			$oStmt = $this->stmt;
		return $oStmt->fetch($iMode);
	}

	public function pdo_fetchAll($iMode = PDO::FETCH_ASSOC, PDOStatement $oStmt = null)
	{
		if (empty($oStmt))
			$oStmt = $this->stmt;
		return $oStmt->fetchAll($iMode);
	}

	public function pdo_closeCursor(PDOStatement $oStmt = null)
	{
		if (empty($oStmt))
			$oStmt = $this->stmt;
		$oStmt->closeCursor();
		$oStmt = null;
		//$this->stmt = null;
	}

	public function pdo_rowCount(PDOStatement $oStmt = null)
	{
		if (empty($oStmt))
			$oStmt = $this->stmt;
		return $oStmt->rowCount();
	}





	/**
	 * Execute a standard SELECT/INSERT/UPDATE/DELETE query
	 *
	 * @param string $sQuery The query
	 * @param array $aParams The params
	 */
	public function execute($sQuery, $aParams = null)
	{
		// Standard code (no cache)
		self::_beginLogQuery();
		$this->pdo_prepare($sQuery);
		if (!is_null($aParams) && !is_array($aParams))
			$aParams = array($aParams);
		$mRes = $this->pdo_execute($aParams);
		self::_logQuery($sQuery, $aParams);
		return $mRes;
	}




	/**
	 * Execute a SELECT query with optional cache management
	 *
	 * @param string $sQuery The query
	 * @param array $aParams The params
	 * @param int $iCacheTTL The cache time to live in seconds (-1 = no cache, 0 = infinite cache)
	 */
	public function fetchOne($sQuery, $aParams = null, $iCacheTTL = -1, $sCacheKey = '')
	{
		// If cache is enabled
		if ($iCacheTTL > -1)
		{
			$sKey = (!empty($sCacheKey) ? $sCacheKey : self::getQueryCacheKey($sQuery, $aParams));
			$oCache = $GLOBALS['asap_cache'];
			if ($oCache->has($sKey))
			{
				//echo 'get one from cache<br />';
				return $oCache->get($sKey);
			}
			else
			{
				//echo 'set one to cache<br />';
				$this->execute($sQuery, $aParams);
				$aRow = $this->pdo_fetch();
				$this->pdo_closeCursor();
				$oCache->set($sKey, $aRow, $iCacheTTL);

				return $aRow;
			}
		}

		// If cache is not enabled
		$this->execute($sQuery, $aParams);
		$aRow = $this->pdo_fetch();
		$this->pdo_closeCursor();
		return $aRow;
	}

	/**
	 * Execute a SELECT query with optional cache management
	 *
	 * @param string $sQuery The query
	 * @param array $aParams The params
	 * @param int $iCacheTTL The cache time to live in seconds (-1 = no cache, 0 = infinite cache)
	 */
	public function fetchAll($sQuery, $aParams = null, $iCacheTTL = -1, $sCacheKey = '')
	{
		// FIXME : check performance for large result arrays (like 100+ results)

		// If cache is enabled
		if ($iCacheTTL > -1)
		{
			$sKey = (!empty($sCacheKey) ? $sCacheKey : self::getQueryCacheKey($sQuery, $aParams));
			$oCache = $GLOBALS['asap_cache'];
			if ($oCache->has($sKey))
			{
				//echo 'get all from cache<br />';
				return $oCache->get($sKey);
			}
			else
			{
				//echo 'set all to cache<br />';
				$this->execute($sQuery, $aParams);
				$aRet = $this->pdo_fetchAll();
				$this->pdo_closeCursor();
				$oCache->set($sKey, $aRet, $iCacheTTL);

				return $aRet;
			}
		}

		// If cache is not enabled
		$this->execute($sQuery, $aParams);
		$aRet = $this->pdo_fetchAll();
		$this->pdo_closeCursor();
		return $aRet;
	}


	/**
	 * Compute an IN() clause and return the resulting query-part string
	 *
	 * @param array $aValues
	 */
	public function computeINClause(array $aValues)
	{
		if (empty($aValues))
			return 'IN("")';

		$sSql = '';
		foreach ($aValues as $mVal)
			$sSql .= ($sSql == '' ? '' : ', ') . $this->quote($mVal);

		return 'IN(' . $sSql . ')';
	}

	public function pdo_beginTransaction()
	{
		$this->pdo->beginTransaction();
	}

	public function pdo_commit()
	{
		$this->pdo->commit();
	}

	public function pdo_rollBack()
	{
		$this->pdo->rollBack();
	}


	public function getFields($sTable)
	{
		if (empty($sTable))
			return array();

		$aRows = $this->fetchAll('DESC ' . $sTable);
		$aRes = array();
		foreach ($aRows as $aRow)
			$aRes[] = $aRow['Field'];
		return $aRes;
	}



	/**
	 * Begin to log a query
	 */
	protected static function _beginLogQuery()
	{
		if (empty(self::$_debug))
			return;

		self::$_queryStartTime = microtime(true);
	}

	/**
	 * Log a query
	 *
	 * @param unknown_type $sQuery
	 */
	protected static function _logQuery(&$sQuery, &$aParams = array())
	{
		if (empty(self::$_debug))
			return;

		$fEndTime = microtime(true);
		$iTime = round(($fEndTime - self::$_startTime) * 1000); // ms
		$iDuration = round(($fEndTime - self::$_queryStartTime) * 1000, 2); // ms
		self::$_log[] = array('query' => $sQuery, 'params' => $aParams, 'time' => $iTime, 'duration' => $iDuration, 'backtrace' => debug_backtrace());
	}

	/**
	 * Get the queries log
	 */
	public static function getLog()
	{
		return self::$_log;
	}

	/**
	 * Get the cache key for that query and those params
	 *
	 * @param string $sQuery
	 * @param array $aParams
	 */
	protected static function getQueryCacheKey(&$sQuery, &$aParams)
	{
		$sArray = '';
		if (!empty($aParams))
			foreach ($aParams as $sKey => &$mVal)
				$sArray .= $sKey . ':' . $mVal . '|';
		return md5($sQuery . '|||' . $sArray);
	}

	protected function onError($oObj)
	{
		$aInfo = $oObj->errorInfo();
		throw new Exception('SQL Error : [' . $aInfo[0] . '][' . $aInfo[1] . '] ' . $aInfo[2] . '<br />Query : ' . $oObj->queryString);
	}




	public function getDb()
	{
		return $this->db;
	}
}
