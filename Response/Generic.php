<?php
/**
 * @package Asap/Response
 */

/**
 * Generic response to change default templating behavior in action
 *
 * @package Asap/Response
 * @author Lideln
 */
abstract class Asap_Response_Generic
{
	protected $_engine;
	protected $_template;
	protected $_params;


	protected function __construct($sEngine, $sTemplate, $aParams)
	{
		$this->_engine = $sEngine;
		$this->_template = $sTemplate;
		$this->_params = $aParams;
	}


	public function getEngine()
	{
		return $this->_engine;
	}

	public function getTemplate()
	{
		return $this->_template;
	}

	public function &getParams()
	{
		return $this->_params;
	}
}
