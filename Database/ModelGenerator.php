<?php
/**
 * @package Asap/Database
 */

/**
 * Model generator used in console
 *
 * @package Asap/Database
 * @author Lideln
 */
class Asap_Database_ModelGenerator
{
	public static function batch($sTable = '', $bForce = false)
	{
		$aModels = array();
		if (!empty($sTable))
			$aModels[] = $sTable;
		else
		{
			$oDB = $GLOBALS['asap']->getDB();
			// Get all models to generate
			$aList = $oDB->fetchAll('SHOW TABLES');
			foreach ($aList as $aTable)
				$aModels[] = current($aTable);
		}

		$iLen = 0;
		foreach ($aModels as $sModel)
			if (strlen($sModel) > $iLen)
				$iLen = strlen($sModel);

		foreach ($aModels as $sModel)
		{
			echo 'Generating model for table ' . str_pad($sModel, $iLen + 2, '.') . ' ';
			$aRet = self::generate($sModel, $bForce);
			echo ($aRet['success'] ? 'Success' : 'Failed!'), (empty($aRet['message']) ? '' : ' (' . $aRet['message'] . ')');
			echo PHP_EOL;
		}
	}

	/**
	 * Generate a model for the given table name
	 *
	 * @param string $sTable
	 * @param bool $bForce Beware! If true, will overwrite any existing model!
	 *
	 * @return bool
	 */
	protected static function generate($sTable, $bForce = false)
	{
		$sClass = Asap_Database_Model::getClassFromTable($sTable);
		$sBase = $sClass . 'Base';
		$sPath = ASAP_DIR_MODEL . 'base/';
		if (!file_exists($sPath))
			mkdir($sPath, 0775, true);
		$sDest = ASAP_DIR_MODEL . 'base/' . $sBase . '.php';
		$bExists = file_exists($sDest);

		if ($bExists)
			unlink($sDest);

		$oDB = $GLOBALS['asap']->getDB();

		// Get table definition
		$aFields = $oDB->fetchAll('SELECT * FROM information_schema.columns WHERE table_schema = ? AND table_name = ?', array($oDB->getDb(), $sTable));//'DESC ' . $sTable);
		if (empty($aFields))
			return array('success' => false, 'message' => 'table does not exists');

		//var_dump($aFields);

		$sFields = '';
		$sFunctions = '';
		$sExtraFields = '';
		$aPKs = array();
		foreach ($aFields as $aField)
		{
			$sField = $aField['COLUMN_NAME'];
			if ($aField['COLUMN_KEY'] == 'PRI')
				$aPKs[] = $sField;
			$sFields .= self::manageField($aField);
			$sExtraField = '';
			$sFunctions .= self::manageForeignKey($sField, $sExtraField);
			$sExtraFields .= self::manageField($sExtraField);
		}

		$sPK = '';
		$sPKCond = '';
		if (count($aPKs) == 1)
		{
			$sPK = "'" . $aPKs[0] . "'";
			$sPKCond = $aPKs[0] . ' = :' . $aPKs[0];
		}
		else
		{
			foreach ($aPKs as $sTmp)
			{
				$sPK .= ($sPK == '' ? 'array(' : ", ") . "'" . $sTmp . "'";
				$sPKCond .= ($sPKCond == '' ? '' : ' AND ') . $sTmp . ' = :' . $sTmp;
			}
			$sPK .= ')';
		}

		$aRow = $oDB->fetchOne('SELECT * FROM information_schema.tables WHERE table_schema = ? AND table_name = ?', array($oDB->getDb(), $sTable));//'DESC ' . $sTable);

		$aParams = array(
			'%datetime%' => date('Y-m-d H:i:s'),
			'%table_name%' => $sTable,
			'%class_name%' => $sBase,
			'%table_comment%' => $aRow['TABLE_COMMENT'],
			'%fields%' => $sFields,
			'%extra_fields%' => $sExtraFields,
			'%functions%' => $sFunctions,
			'%pk%' => $sPK,
			'%pk_cond%' => $sPKCond
		);
		$sFile = file_get_contents(realpath(dirname(__FILE__)) . '/model.inc.php');
		$sFile = str_replace(array_keys($aParams), array_values($aParams), $sFile);

		file_put_contents($sDest, $sFile);



		// Model extending its base (if not already existing)
		$sDest = ASAP_DIR_MODEL . $sClass . '.php';
		$bExists = file_exists($sDest);
		if ($bExists)
		{
			if (!$bForce)
				return array('success' => false, 'message' => 'model file exists and "force" option was NOT activated');
			else
				unlink($sDest);
		}

		$sData = <<<EOT
<?php

require_once(ASAP_DIR_MODEL . 'base/$sBase.php');

class $sClass extends $sBase
{

}

EOT;
		file_put_contents($sDest, $sData);


		return array('success' => true, 'message' => ($bExists ? 'WARNING: model file was overwritten' : ''));
	}


	protected static function manageField($aField)
	{
		if (empty($aField))
			return '';

		$sData = '';

		if (is_string($aField))
		{
			$sData = <<<EOT
	/**
	 * @var
	 */
	protected \${$aField};
EOT;
		}
		else
		{
			$sKeyType = (empty($aField['COLUMN_KEY']) ? '' : '(' . $aField['COLUMN_KEY'] . ') ');
			$sDefault = $aField['COLUMN_DEFAULT'];
			if (is_null($sDefault))
				$sDefault = 'NULL';
			else if ($sDefault === '')
				$sDefault = "''";
			else if (!ctype_digit($sDefault))
				$sDefault = "'" . $sDefault . "'";
			$sComment = $aField['COLUMN_COMMENT'];
			if (!empty($sComment))
				$sComment = PHP_EOL . "\t * " . $sComment;
			$sData = <<<EOT
	/**{$sComment}
	 * @var {$sKeyType}{$aField['COLUMN_TYPE']}
	 * @default {$sDefault}
	 */
	protected \${$aField['COLUMN_NAME']}
EOT;
			$bIsNullable = ($aField['IS_NULLABLE'] == 'YES');
			$bIsDefaultNull = ($aField['COLUMN_DEFAULT'] === null);
			if ($bIsNullable || !$bIsDefaultNull)
			{
				if ($bIsDefaultNull)
					$sDefault = 'null';
				$sData .= ' = ' . $sDefault;
			}
			$sData .= ';' . "\r\n";
		}

		return $sData . PHP_EOL;
	}

	protected static function manageForeignKey($sField, &$sExtraField)
	{
		if (mb_substr($sField, -3) != '_id')
			return $sExtraField = '';

		$sForeign = mb_substr($sField, 0, -3);
		$sExtraField = '_' . $sForeign;
		$sForeignClass = Asap_Database_Model::getClassFromTable($sForeign);
		$sForeignClassShort = substr($sForeignClass, 0, -5);
		$sRet = <<<EOT
	public function get{$sForeignClassShort}()
	{
		if (empty(\$this->{$sExtraField}) && !empty(\$this->{$sField}))
			\$this->{$sExtraField} = new {$sForeignClass}(\$this->{$sField});

		return \$this->{$sExtraField};
	}


EOT;
		return $sRet;
	}

}
