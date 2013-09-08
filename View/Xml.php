<?php


/**
 * Xml view class
 *
 * @author Lideln
 */
class Asap_View_Xml extends Asap_View_Generic
{
	protected function _init()
	{
		header('Content-Type: text/xml; charset=UTF-8');
	}

	protected function _display($sTemplate, &$mParams)
	{
		//if ($bReturn)
		//	return json_encode($mParams);

		echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>";
		$this->displayArrayToXML($mParams);
	}

	protected function displayArrayToXML($aData, $iTab = 0, $sPrevious = '')
	{
		if (is_string($aData))
		{
			echo $aData;
			return;
		}

		$sSpace = str_repeat("\t", $iTab);

		//var_dump($iTab);
		//var_dump($sSpace);

		// parse the array for data and output xml
		foreach ($aData as $sTag => $mVal)
		{
			if (is_int($sTag))
				$sTag = ($sPrevious == '' ? 'item' : substr($sPrevious, 0, -1));
			if (!is_array($mVal))
				echo PHP_EOL, $sSpace, '<' , $sTag, '>', htmlentities($mVal), '</', $sTag, '>';
			else
			{
				echo PHP_EOL, $sSpace, '<', $sTag, '>';
				$this->displayArrayToXML($mVal, ++$iTab, $sTag);
				echo PHP_EOL, $sSpace, '</', $sTag, '>';
			}
		}
	}
}
