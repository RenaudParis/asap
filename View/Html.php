<?php


/**
 * Html view class
 *
 * @author Lideln
 */
class Asap_View_Html extends Asap_View_Generic
{
	protected function _display($sTemplate, &$aParams, $bReturn = false)
	{
		$oAsap = Asap_Core_Asap::getInstance();
		$oCache = $oAsap->getCache();
		$sKey = 'engines.html.variables';
		if ($oCache->has($sKey))
			$sVarNaming = $oCache->get($sKey);
		else
		{
			$sVarNaming = $oAsap->getParameter('engines.html.variables', 'view');
			$oCache->set($sKey, $sVarNaming);
		}

		// FIXME : mettre en cache le contenu initial ??
		$sContents = file_get_contents(ASAP_DIR_VIEW . $sTemplate);
		$sKeys = array_keys($aParams);
		if ($sVarNaming != '.')
			foreach ($sKeys as &$sKey)
				$sKey = str_replace('.', $sKey, $sVarNaming);
		$sContents = str_replace($sKeys, array_values($aParams), $sContents);
		if ($bReturn)
			return $sContents;
		echo $sContents;
	}
}
