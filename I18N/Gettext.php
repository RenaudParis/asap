<?php
/**
 * @package Asap/I18N
 */

/**
 * Gettext I18N management
 *
 * @package Asap/I18N
 * @author Lideln
 */
class Asap_I18N_Gettext extends Asap_I18N_Generic
{
	public function trans($sMsg, $aParams = array())
	{
		if (empty($sMsg))
			return $sMsg;
		return $this->_paramTrans(gettext($sMsg), $aParams);
	}


	protected function _init()
	{
		$sLng = $this->_locale;
		$sDomain = $this->_params['domain'];

		putenv('LC_ALL=' . $sLng);
		setlocale(LC_ALL, $sLng);
		bindtextdomain($sDomain, ASAP_DIR_I18N);
		textdomain($sDomain);
	}
}
