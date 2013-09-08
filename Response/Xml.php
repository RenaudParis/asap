<?php
/**
 * @package Asap/Response
 */

/**
 * XML response
 *
 * @package Asap/Response
 * @author Lideln
 */
class Asap_Response_Xml extends Asap_Response_Generic
{
	public function __construct($aParams)
	{
		parent::__construct('xml', '', $aParams);
	}
}
