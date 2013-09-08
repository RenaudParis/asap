<?php


/**
 * Cache_PrefixModel model (generated 2012-10-13 19:45:47)
 * Table : cache_prefix
 *
 */
class Asap_Database_CachePrefixModel extends Asap_Database_Model
{
	/**
	 * @var (PRI) int(10) unsigned
	 * @default NULL
	 */
	protected $id;
	/**
	 * @var (MUL) varchar(40)
	 * @default NULL
	 */
	protected $name;
	/**
	 * @var varchar(200)
	 * @default NULL
	 */
	protected $prefix;









	public static function getAll()
	{
		return self::getDB()->fetchAll('SELECT * FROM cache_prefix');
	}

	public static function updatePrefix($sName, $sPrefix)
	{
		$sReq = 'SELECT id FROM cache_prefix WHERE name = ? LIMIT 1';
		$aRow = self::getDB()->fetchOne($sReq, $sName);
		if (empty($aRow))
			$sReq = 'INSERT INTO cache_prefix (prefix, name) VALUES (?, ?)';
		else
			$sReq = 'UPDATE cache_prefix SET prefix = ? WHERE name = ? LIMIT 1';
		self::getDB()->execute($sReq, array($sPrefix, $sName));
	}






	/**
	 * Default ctor with precalculated attributes
	 */
	public function __construct($mPK = null)
	{
		$this->__table = 'cache_prefix';
		$this->__pk = 'id';
		$this->__pk_cond = 'id = :id';
		parent::__construct($mPK);
	}



}


