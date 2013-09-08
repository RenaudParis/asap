<?php


abstract class Asap_Util_Util
{
	/**
	 * Check if we are using a mobile or tablet device
	 */
	public static function isMobileDevice($bStoreInSession = true)
	{
		if (isset($_GET['mobile']))
			return $_GET['mobile'] && $_GET['mobile'] !== 'false';
		return true;
		// Store in session (across multiple page loads)
		if ($bStoreInSession)
		{
			if (!isset($_SESSION['asap_is_mobile_device']))
				$_SESSION['asap_is_mobile_device'] = self::_isMobileDevice();

			return $_SESSION['asap_is_mobile_device'];
		}

		// Do not store in session (stored only for current page load)
		static $bIsMobile = null;
		if ($bIsMobile === null)
			$bIsMobile = self::_isMobileDevice();

		return $bIsMobile;
	}

	public static function removeAccents($sStr)
	{
		return strtr($sStr, 'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ', 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
	}

	/**
	 * Called only once to check if we are using a smarphone or tablet
	 */
	protected static function _isMobileDevice()
	{
		require_once(ASAP_DIR_VENDOR . 'MobileDetect/Mobile_Detect.php');
		$oDetect = new Mobile_Detect();
		return $oDetect->isMobile();
	}


	/**
	 * Do a POST using CURL
	 * @param unknown_type $sUrl
	 * @param unknown_type $aData
	 */
	public static function post($sUrl, $aData = null, $bGetResult = true)
	{
		$sPostData = '';
		if (!empty($aData))
			$sPostData = $aData;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $sUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, $bGetResult);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $sPostData);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$sResult = curl_exec($ch);
		curl_close($ch);
		return $sResult;
	}

	/**
	 * Formats a number using current language
	 *
	 * @param unknown_type $fNumber
	 */
	public static function numberFormat($fNumber)
	{
		switch ($GLOBALS['asap']->getCurrentLanguage())
		{
			case 'en':
				return number_format($fNumber);
			default:
				return number_format($fNumber, 0, ',', '.');
		}
		return $fNumber;
	}

	/**
	 * Get the difference in days between two dates
	 *
	 * @param string $sDate1 First date in Y-m-d format
	 * @param string $sDate2 Last date in Y-m-d format
	 */
	public static function dateDiff($sDate1, $sDate2)
	{
		/*
		$oDT1 = new DateTime($sDate1);
		$oDT2 = new DateTime($sDate2);
		return $oDT1->diff($oDT2)->days;
		*/
		return (strtotime($sDate2) - strtotime($sDate1)) / 86400;
	}

	/**
	 * Transforms a FR date into an EN date
	 *
	 * @param string $sDateFR
	 */
	public static function dateFRtoEN($sDateFR)
	{
		$aParts = date_parse_from_format('d/m/Y', $sDateFR);
		if ($aParts['warning_count'] || $aParts['error_count'])
			return false;
		return $aParts['year'] . '-' . str_pad($aParts['month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($aParts['day'], 2, '0', STR_PAD_LEFT);
	}

	/**
	 * Transforms a EN date into an FR date
	 *
	 * @param string $sDateEN
	 */
	public static function dateENtoFR($sDateEN)
	{
		$aParts = date_parse_from_format('Y-m-d', $sDateEN);
		if ($aParts['warning_count'] || $aParts['error_count'])
			return false;
		return str_pad($aParts['day'], 2, '0', STR_PAD_LEFT) . '/' . str_pad($aParts['month'], 2, '0', STR_PAD_LEFT) . '/' . $aParts['year'];
	}

	/**
	 * Get the remote IP
	 */
	public static function getIP()
	{
		static $sIP = null;
		if ($sIP === null)
		{
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && substr($_SERVER['HTTP_X_FORWARDED_FOR'], 0, 7) != 'unknown' && strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ':') === false)
				$sIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
			else if (!empty($_SERVER['HTTP_CLIENT_IP']) && substr($_SERVER['HTTP_CLIENT_IP'], 0, 7) != 'unknown' && strpos($_SERVER['HTTP_CLIENT_IP'], ':') === false)
				$sIP = $_SERVER['HTTP_CLIENT_IP'];
			else
				$sIP = $_SERVER['REMOTE_ADDR'];

			// Contains 2 IPs comma separated
			if (!empty($sIP) && strpos($sIP, ',') != false)
			{
				$aIPs = explode(',', $sIP);
				$sIP = $aIPs[0];
			}
		}

		return $sIP;
	}

	/**
	 * Detect the proxy type
	 * @param unknown_type $sIP
	 */
	public static function detectProxyType($sIP)
	{
		if (isset($_SERVER['ASAP_ENV']) && $_SERVER['ASAP_ENV'] == 'dev')
			return 'None';

		$scan_headers = array(
			'HTTP_VIA',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED',
			'HTTP_CLIENT_IP',
			'HTTP_FORWARDED_FOR_IP',
			'VIA',
			'X_FORWARDED_FOR',
			'FORWARDED_FOR',
			'X_FORWARDED',
			'FORWARDED',
			'CLIENT_IP',
			'FORWARDED_FOR_IP',
			'HTTP_PROXY_CONNECTION'
		);

		$flagProxy = false;

		foreach ($scan_headers as $i)
			if (isset($_SERVER[$i]))
			{
				echo 'Yes Flag Proxy : ' . $i . '<br>';
				$flagProxy = true;
				break;
			}

		if (!$flagProxy)
		{
			try
			{
				if (in_array($_SERVER['REMOTE_PORT'], array(8080,80,6588,8000,3128,553,554)))
				{
					$flagProxy = true;
				}
				else
				{
					$bOpen = @fsockopen($_SERVER['REMOTE_ADDR'], 80, $errno, $errstr, 3);
					if ($bOpen)
					{
						$flagProxy = true;
					}
				}
			}
			catch (ErrorException $e)
			{
				$flagProxy = false;
			}
		}

		// Proxy LookUp
		if ($flagProxy && !empty($_SERVER['REMOTE_ADDR']))
		{
			// Transparent Proxy
			// REMOTE_ADDR = proxy IP
			// HTTP_X_FORWARDED_FOR = your IP
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])
					&& $_SERVER['HTTP_X_FORWARDED_FOR'] == $sIP)
				return 'Transparent Proxy';

			// Simple Anonymous Proxy
			// REMOTE_ADDR = proxy IP
			// HTTP_X_FORWARDED_FOR = proxy IP
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])
					&& $_SERVER['HTTP_X_FORWARDED_FOR'] == $_SERVER['REMOTE_ADDR'])
				return 'Simple Anonymous (Transparent) Proxy';

			// Distorting Anonymous Proxy
			// REMOTE_ADDR = proxy IP
			// HTTP_X_FORWARDED_FOR = random IP address
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])
					&& $_SERVER['HTTP_X_FORWARDED_FOR'] != $_SERVER['REMOTE_ADDR'])
				return 'Distorting Anonymous (Transparent) Proxy';

			// Anonymous Proxy
			// HTTP_X_FORWARDED_FOR = not determined
			// HTTP_CLIENT_IP = not determined
			// HTTP_VIA = determined
			if (empty($_SERVER['HTTP_X_FORWARDED_FOR'])
					&& empty($_SERVER['HTTP_CLIENT_IP'])
					&& !empty($_SERVER['HTTP_VIA']))
				return 'Anonymous Proxy';

			// High Anonymous Proxy
			// REMOTE_ADDR = proxy IP
			// HTTP_X_FORWARDED_FOR = not determined
			return 'High Anonymous Proxy';
		}

		return 'None';
	}

	/**
	 * Check if a user is behind a proxy
	 */
	public static function isUsingProxy($bAutodetect = true)
	{
		if (!$bAutodetect)
		{
			$sIP = self::getIP();
			if (!isset($_SESSION['proxy_detection'][$sIP]))
				return null;

			return $_SESSION['proxy_detection'][$sIP] != 'None';
		}

		return (self::getUsedProxy() != 'None');
	}

	/**
	 * Get the type of proxy a user is behind
	 */
	public static function getUsedProxy()
	{
		$sIP = self::getIP();
		if (isset($_SESSION))
		{
			if (!isset($_SESSION['proxy_detection']))
				$_SESSION['proxy_detection'] = array();
			if (isset($_SESSION['proxy_detection'][$sIP]))
				return $_SESSION['proxy_detection'][$sIP];
		}

		$sTypeUsing = self::detectProxyType($sIP);

		if (isset($_SESSION))
			$_SESSION['proxy_detection'][$sIP] = $sTypeUsing;

		return $sTypeUsing;
	}

	/**
	 * Try to trim the given end off the end of the given string
	 *
	 * @param string $sStr The source string
	 * @param string $sEnd The end to find and remove
	 */
	public static function trimEnd($sStr, $sEnd)
	{
		$iLen = strlen($sEnd);
		if (substr($sStr, - $iLen) == $sEnd)
			return substr($sStr, 0, - $iLen);
		return $sStr;
	}

	/**
	 * Check if age is above given value
	 *
	 * @param string $sDateOfBirth SQL format date (Y-m-d)
	 * @param int $iAge
	 */
	public static function isAgeOver($sDateOfBirth, $iAge = 18)
	{
		return self::getAge($sDateOfBirth) >= $iAge;
	}

	/**
	 * Calculate age
	 *
	 * @param string $sDateOfBirth SQL format date (Y-m-d)
	 */
	public static function getAge($sDateOfBirth)
	{
		static $now = null;
		if ($now == null)
			$now = new DateTime();

		return $now->diff(new DateTime($sDateOfBirth))->y;
	}

	/**
	 * Generate a password
	 */
	public static function generatePassword($iLength = 10, $bSpecialChars = false)
	{
		$sChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		if ($bSpecialChars)
			$sChars .= '!?.,+-';

		$sPassword = '';
		$iLen = strlen($sChars) - 1;
		for ($i = 0; $i < $iLength; $i++)
			$sPassword .= $sChars[rand(0, $iLen)];

		return $sPassword;
	}

	/**
	 * Generate the slug for the given value
	 *
	 * @param string $str The string to get the slug from
	 * @param string $default Optional, return this slug instead if not empty
	 */
	public static function slug($str, $default = null)
	{
		if (!empty($default))
			return $default;

		return self::generateSlug($str);
	}

	private static function generateSlug($str, $replace = array(), $delimiter = '-')
	{
		if (!empty($replace))
			$str = str_replace((array)$replace, ' ', $str);

		$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
		$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
		$clean = strtolower(trim($clean, '-'));
		$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

		return $clean;
	}

	/**
	 * Recursively merge 2 arrays with overwrite
	 *
	 * @param array $aArr1
	 * @param array $aArr2
	 */
	public static function array_merge(array &$aArr1, array &$aArr2)
	{
		foreach ($aArr2 as $key => &$val)
		{
			if (is_array($val) && array_key_exists($key, $aArr1))
			{
				if (!is_array($aArr1[$key]))
					$aArr1[$key] = array();
				//if (key($val) === 0)
				//	$val = array_merge($val, $aArr1[$key]);
				//else
					self::array_merge($aArr1[$key], $val);
			}
			else
				$aArr1[$key] = $val;
		}

		return $aArr1;
	}

	/**
	 * Recursively remove a directory (or its content only)
	 *
	 * @param string $sPath The directory to remove (or empty)
	 * @param bool $bRemoveSelf Should we remove this directory ? (or else only its content)
	 */
	public static function rmdir($sPath, $bRemoveSelf = true)
	{
		if (is_dir($sPath))
		{
			$aFiles = scandir($sPath);
			foreach ($aFiles as $sFile)
			{
				if ($sFile == '.' || $sFile == '..')
					continue;
				$sFile = $sPath . '/' . $sFile;
				if (is_dir($sFile))
					self::rmdir($sFile);
				else
					unlink($sFile);
			}

			if ($bRemoveSelf)
				rmdir($sPath);
		}
	}

}
