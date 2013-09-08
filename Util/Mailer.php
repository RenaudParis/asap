<?php


class Asap_Util_Mailer
{
	protected static $_conf = null;
	// Array of the available mailers
	protected static $_mailers = null;



	/**
	 * Send a batch of emails from DB
	 *
	 * @param int $iAmount
	 */
	public static function sendFromDB($iAmount = -1)
	{
		// Get params
		self::_getConf();

		// Do not send mails if not active
		if (empty(self::$_conf['active']) || empty(self::$_conf['db']['active']))
			return false;

		// Use conf batch amount if not specified
		if ($iAmount == -1)
			$iAmount = self::$_conf['db']['batch'];

		// Retrieve emails
		$oDB = $GLOBALS['asap']->getDB();
		$sTable = self::$_conf['db']['table'];
		$aMails = $oDB->fetchAll('SELECT * FROM `' . $sTable . '` LIMIT ' . (int)$iAmount);
		$aMailsToRemove = array();
		if (!empty($aMails))
			foreach ($aMails as &$aMail)
			{
				$aMail['from'] = unserialize($aMail['from']);
				$aMail['to'] = unserialize($aMail['to']);
				$aMail['cc'] = unserialize($aMail['cc']);
				$aMail['bcc'] = unserialize($aMail['bcc']);
				self::send(true, $aMail);
				$aMailsToRemove[] = $aMail['id'];
			}

		// Delete sent mails
		if (!empty($aMailsToRemove))
			$oDB->execute('DELETE FROM `' . $sTable . '` WHERE id IN(' . implode(', ', $aMailsToRemove) . ')');

		return count($aMailsToRemove);
	}


	/**
	 * Send a Twig email, optionally kept in cache
	 *
	 * @param unknown_type $bNow
	 * @param unknown_type $aInfo
	 * @param unknown_type $sTpl
	 * @param unknown_type $aParams
	 * @param unknown_type $iTTL
	 */
	public static function sendTwig($bNow, $aInfo, $sTpl, $aParams = array(), $iTTL = -1)
	{
		// Get params
		self::_getConf();

		// Do not send mails if not active
		if (!self::$_conf['active'])
			return false;

		$aInfo['body'] = '';
		if ($iTTL != -1) // To be retrieved from cache
		{
			$sKey = 'email_twig_' . str_replace('/', '_', $sTpl);
			$oCache = $GLOBALS['cache'];
			if ($oCache->has($sKey))
				$aInfo['body'] = $oCache->get($sKey);
			else
			{
				// Generate cache
				$oTwig = new Asap_View_Twig();
				$sTpl = $oTwig->getLocalizedTpl($sTpl, $GLOBALS['asap']->getCurrentLanguage());
				$aInfo['body'] = $oTwig->render($sTpl, $aParams, true);
				$oCache->set($sKey, $aInfo['body'], $iTTL);
			}
		}
		else // If not to be retrieved from cache, simply render it
		{
			$oTwig = new Asap_View_Twig();
			$sTpl = $oTwig->getLocalizedTpl($sTpl, $GLOBALS['asap']->getCurrentLanguage());
			$aInfo['body'] = $oTwig->render($sTpl, $aParams, true);
		}

		// Send the email
		return self::send($bNow, $aInfo);
	}


	/**
	 * Send an email
	 *
	 * @param unknown_type $bNow
	 * @param unknown_type $aInfo
	 */
	public static function send($bNow, $aInfo)
	{
		//$aTo, $sSubject, $sBody, $aFrom = null, $aCC = array(), $aBCC = array()
		if (empty($aInfo['to']) || empty($aInfo['subject']) || empty($aInfo['body']))
			return false;

		// Get params
		self::_getConf();

		// Do not send mails if not active
		if (!self::$_conf['active'])
			return false;


		if (empty($aInfo['cc']))
			$aInfo['cc'] = array();
		if (empty($aInfo['bcc']))
			$aInfo['bcc'] = array();

		// In dev mode, useful to send ourselves the emails
		if (!empty(self::$_conf['overwrite_to']))
			$aInfo['to'] = self::$_conf['overwrite_to'];

		// Get default sender
		$sSender = null;
		if (empty($aInfo['from']))
		{
			$sSender = (empty($aInfo['sender']) ? self::$_conf['default_sender'] : $aInfo['sender']);
			$aInfo['from'] = self::getSender($sSender);
		}

		if (self::$_conf['debug'])
		{
			echo 'Sending email!<br />';
			var_dump($aInfo);
			return true;
		}

		if (empty($aInfo['plain']))
		{
			//require_once(ASAP_DIR_VENDOR . 'html2text/html2text.php');
			require_once(ASAP_DIR_VENDOR . 'html2text/class.html2text.inc.php');
			$h2t = new html2text($aInfo['body']);
			$aInfo['plain'] = $h2t->get_text();//html2text($aInfo['body']);
			//header('Content-type: text/plain; charset=utf-8');
			//echo $aInfo['plain'];
			//die();
		}

		// Save in database...
		if (!$bNow && !empty(self::$_conf['db']['active']))
		{
			$sClass = Asap_Database_Model::getClassFromTable(self::$_conf['db']['table']);
			$oObj = new $sClass();
			$aData = array('subject' => $aInfo['subject'], 'body' => $aInfo['body'], 'to' => serialize($aInfo['to']), 'from' => serialize($aInfo['from']), 'cc' => serialize($aInfo['cc']), 'bcc' => serialize($aInfo['bcc']));
			$oObj->loadData_p($aData);
			$oObj->save();
			return true;
		}

		// ... Or send it now
		//<img src="$message->embed(Swift_Image::fromPath('http://site.tld/logo.png'))" />
		try
		{
			require_once(ASAP_DIR_VENDOR . 'Swift/swift_required.php');
			$oMsg = Swift_Message::newInstance()
				->setSubject($aInfo['subject'])
				->setBody($aInfo['body'], 'text/html')
				->setFrom($aInfo['from'])
				->setTo($aInfo['to'])
				->setCc($aInfo['cc'])
				->setBcc($aInfo['bcc']);
			if (!empty($aInfo['plain']))
				$oMsg->addPart($aInfo['plain'], 'text/plain');
			self::_getMailer($sSender)->send($oMsg);
		}
		catch (Exception $e)
		{
			$sExceptionClass = get_class($e);

			// Bad email address
			if ($sExceptionClass == 'Swift_RfcComplianceException')
				return false;

			file_put_contents(ASAP_DIR_LOG . 'mailer_errors_' . date('Y-m-d') . '.log', '*** ' . date('Y-m-d H:i:s') . ' ***' . "\r\n" . $e->getMessage() . "\r\n" . json_encode(debug_backtrace()) . "\r\n\r\n", FILE_APPEND);
			$oAsap = $GLOBALS['asap'];
			if (!empty(self::$_conf['addresses']['_fallback']))
			{
				try
				{
					$oMsg->setFrom(self::getSender('_fallback'));
					self::_getMailer('_fallback')->send($oMsg);
					return true;
				}
				catch (Exception $e2)
				{
					file_put_contents(ASAP_DIR_LOG . 'mailer_errors_' . date('Y-m-d') . '.log', '*** ' . date('Y-m-d H:i:s') . ' ***' . "\r\n" . $e->getMessage() . "\r\n" . json_encode(debug_backtrace()) . "\r\n\r\n", FILE_APPEND);
					if ($oAsap->isDebug())
						throw $e2;
				}
			}
			else
			{
				if ($oAsap->isDebug())
					throw $e;
			}
			return false;
		}

		return true;
	}

	public static function getSender($sType)
	{
		self::_getConf();
		$aRow = self::$_conf['addresses'][$sType];
		if (empty($aRow))
			throw new Exception('address ' . $sType . ' not found');
		return array($aRow['email'] => $aRow['label']);
	}

	public static function getDefaultSender()
	{
		static $aSender = null;
		if ($aSender === null)
		{
			self::_getConf();
			$aSender = self::getSender(self::$_conf['default_sender']);
		}
		return $aSender;
	}

	protected static function _getConf()
	{
		if (self::$_conf === null)
			self::$_conf = $GLOBALS['asap_cache']->getSet('asap.config.mailer', function()
			{
				$oAsap = $GLOBALS['asap'];
				return $oAsap->getParameter('mailer', 'asap');
			});
		return self::$_conf;
	}

	protected static function _getMailer($sSender = null)
	{
		if (empty($sSender))
			$sSender = '__default__';

		if (empty(self::$_mailers[$sSender]))
		{
			self::_getConf();
			$sHost = self::$_conf['host'];
			$sPort = self::$_conf['port'];
			$sEncryption = self::$_conf['encryption'];
			$sUser = self::$_conf['user'];
			$sPass = self::$_conf['password'];
			// If the default conf is overloaded for this specific sender
			if ($sSender != '__default__' && !empty(self::$_conf['addresses'][$sSender]))
			{
				$aTmpSender = self::$_conf['addresses'][$sSender];
				if (!empty($aTmpSender['host']))
					$sHost = $aTmpSender['host'];
				if (!empty($aTmpSender['port']))
					$sPort = $aTmpSender['port'];
				if (!empty($aTmpSender['encryption']))
					$sEncryption = $aTmpSender['encryption'];
				if (!empty($aTmpSender['user']))
					$sUser = $aTmpSender['user'];
				if (!empty($aTmpSender['password']))
					$sPass = $aTmpSender['password'];
			}
			$oTransport = Swift_SmtpTransport::newInstance($sHost, $sPort, $sEncryption);
			$oTransport->setUsername($sUser);
			$oTransport->setPassword($sPass);
			self::$_mailers[$sSender] = Swift_Mailer::newInstance($oTransport);
		}

		return self::$_mailers[$sSender];
	}
}
