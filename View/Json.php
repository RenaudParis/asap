<?php


/**
 * Json view class
 *
 * @author Lideln
 */
class Asap_View_Json extends Asap_View_Generic
{
	protected function _init()
	{
		header('Content-Type: application/json; charset=UTF-8');
	}

	protected function _display($sTemplate, &$aParams, $bReturn = false)
	{
		if ($bReturn)
			return json_encode($aParams);

		echo json_encode($aParams);
	}
}
