<?php


/**
 * Php view class
 *
 * @author Lideln
 */
class Asap_View_Php extends Asap_View_Generic
{
	protected function _display($sTemplate, &$aParams, $bReturn = false)
	{
		foreach ($aParams as $sKey => &$mVal)
			$$sKey = $mVal;
		unset($aParams);
		if ($bReturn)
			ob_start();
		require(ASAP_DIR_VIEW . $sTemplate);
		if ($bReturn)
			return ob_get_clean();
	}
}
