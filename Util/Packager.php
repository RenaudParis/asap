<?php


class Asap_Util_Packager
{
	protected static $_aParams = null;



	/**
	 * Load the package or the list of contained files if not active
	 *
	 * @param string $sType
	 */
	public static function loadPackage($sName, $bUseTags = true)
	{
		self::_init();

		$sRoot = Asap_View_Generic::getStaticRoot();

		$aFiles = self::_package($sName);
		$aParams = self::$_aParams[$sName];
		$sType = $aParams['type'];
		if ($sType == 'css')
			foreach ($aFiles as $sFile)
				echo '<link type="text/css" rel="stylesheet" href="', $sRoot, $sFile, '" />';
		else
		{
			if ($bUseTags)
			{
				foreach ($aFiles as $sFile)
					echo '<script type="text/javascript" src="', $sRoot, $sFile, '"></script>';
			}
			else
			{
				echo '[';
				foreach ($aFiles as $i => $sFile)
					echo ($i == 0 ? '' : ', '), '"', $sRoot, $sFile, '"';
				echo ']';
			}
		}
	}





	/**
	 * Init
	 */
	protected static function _init()
	{
		if (self::$_aParams !== null)
			return;

		$aParams = Asap_View_Generic::getConfig();//$GLOBALS['asap_cache']->getSet('asap.config.view', 'Asap_View_Generic::_cache_getConf');
		self::$_aParams = $aParams['packager'];
	}

	/**
	 * Get the package filename for the given type
	 *
	 * @param string $sType
	 */
	protected static function _getFilename($sName)
	{
		$aParams = self::$_aParams[$sName];
		return self::$_aParams[$sName]['name'] . '.asap_pack.' . self::$_aParams[$sName]['version'] . '.' . $aParams['type'];
	}

	/**
	 * Get the package files
	 * @param unknown_type $sType
	 * @param unknown_type $aArray
	 */
	protected static function _getPackageFiles($sName)
	{
		$aParams = self::$_aParams[$sName];

		$sType = $aParams['type'];
		$sBaseFolder = (empty($aParams['folder']) ? $sType : $aParams['folder']) . '/';

		// Files are listed as array, return them
		if (is_array($aParams['files']))
		{
			$aRet = array();
			foreach ($aParams['files'] as $sFile)
				$aRet[] = $sBaseFolder . $sFile;
			return $aRet;
		}

		// User wants all files to be included
		if ($aParams['files'] == '*')
		{
			$sFileType = '.' . $sType;
			$iFileTypeLen = strlen($sFileType);
			$aAllFiles = array();
			$aFiles = scandir(ASAP_DIR_WEB . $sBaseFolder);
			foreach ($aFiles as $sFile)
				if ($sFile != '.' && $sFile != '..' && substr($sFile, -$iFileTypeLen) == $sFileType && strpos($sFile, '.asap_pack.') === false)
					$aAllFiles[] = $sFile;
			return $aAllFiles;
		}

		// Strange config, thus we return nothing
		return array();
	}

	/**
	 * Génère un package sans utiliser la configuration en cache (utile après upload FTP et avant vidage du cache de config)
	 *
	 * @param unknown_type $sType
	 */
	public static function prefetchPackage($sName)
	{
		$aBackupParams = self::$_aParams;

		self::$_aParams = Asap_Core_Asap::loadConf()->get('asap.view.packager');

		self::_package($sName);
		$sFilename = self::_getFilename($sName);

		self::$_aParams = $aBackupParams;

		return $sFilename;
	}

	/**
	 * Create a package (if necessary)
	 *
	 * @param string $sType
	 * @return array The list of files to load
	 */
	protected static function _package($sName)
	{
		$aParams = self::$_aParams[$sName];
		if (empty($aParams['active']))// || $GLOBALS['asap']->isDebug())
			return self::_getPackageFiles($sName);

		$sType = $aParams['type'];
		if ($sType != 'css' && $sType != 'js')
			return array();

		$sFilename = self::_getFilename($sName);
		$sSubFolder = (empty($aParams['folder']) ? $sType : $aParams['folder']) . '/';
		$sBaseFolder = ASAP_DIR_WEB;
		$sFullFilename = $sBaseFolder . $sSubFolder . $sFilename;
		if (file_exists($sFullFilename))
			return array($sSubFolder . $sFilename);

		$aParams = self::$_aParams[$sName];
		$bMinify = $aParams['minify'];
		$sFunc = '_minify_' . $sType;
		$sFullContents = '';

		$aParams['files'] = self::_getPackageFiles($sName);
		//var_dump($aParams['files']);

		if ($sType == 'js')
			require_once(ASAP_DIR_VENDOR . 'jsmin/jsmin.php');

		foreach ($aParams['files'] as $sFile)
		{
			$sFile = $sBaseFolder . $sFile;
			if (!file_exists($sFile))
				die('le fichier ' . $sFile . ' nexiste pas....');
			$sContents = file_get_contents($sFile);
			if ($bMinify)
				self::$sFunc($sContents);
			$sFullContents .= $sContents;
		}
		file_put_contents($sFullFilename, $sFullContents);

		return array($sSubFolder . $sFilename);
	}

	/**
	 * Minify a css contents
	 *
	 * @param string $sContents
	 */
	protected static function _minify_css(&$sContents)
	{
		$sContents = preg_replace('#\s+#', ' ', $sContents);
		$sContents = preg_replace('#/\*.*?\*/#s', '', $sContents);
		$sContents = str_replace('; ', ';', $sContents);
		$sContents = str_replace(': ', ':', $sContents);
		$sContents = str_replace(' {', '{', $sContents);
		$sContents = str_replace('{ ', '{', $sContents);
		$sContents = str_replace(', ', ',', $sContents);
		$sContents = str_replace('} ', '}', $sContents);
		$sContents = str_replace(';}', '}', $sContents);
		return trim($sContents);
	}

	/**
	 * Minify a js contents
	 *
	 * @param string $sContents
	 */
	protected static function _minify_js(&$sContents)
	{
		$sContents = JSMin::minify($sContents);
	}
}
