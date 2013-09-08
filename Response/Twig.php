<?php
/**
 * @package Asap/Response
 */

/**
 * Twig response for custom template for example
 *
 * @package Asap/Response
 * @author Lideln
 */
class Asap_Response_Twig extends Asap_Response_Generic
{
	public function __construct($sTemplate, $aParams = array())
	{
		parent::__construct('twig', $sTemplate, $aParams);
	}
}
