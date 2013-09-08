<?php


/**
 * %class_name% model (generated %datetime%)
 * Table : %table_name%
 * %table_comment%
 */
abstract class %class_name% extends Asap_Database_Model
{
%fields%

%extra_fields%











	/**
	 * Default ctor with precalculated attributes
	 */
	public function __construct($mPK = null)
	{
		$this->__table = '%table_name%';
		$this->__pk = %pk%;
		$this->__pk_cond = '%pk_cond%';
		parent::__construct($mPK);
	}


%functions%
}


