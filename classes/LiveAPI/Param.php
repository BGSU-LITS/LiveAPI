<?php
/**
 * Class method parameter documentation generator.
 *
 * @package    Kohana/Userguide
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class LiveAPI_Param {

	/**
	 * @var  object  ReflectionParameter for this property
	 */
	public $param;

	/**
	 * @var  string  name of this var
	 */
	public $name;

	/**
	 * @var  string  variable type, retrieved from the comment
	 */
	public $type;

	/**
	 * @var  string  default value of this param
	 */
	public $default;

	/**
	 * @var  string  description of this parameter
	 */
	public $description;

	/**
	 * @var  boolean  is the parameter passed by reference?
	 */
	public $reference = FALSE;

	/**
	 * @var  boolean  is the parameter optional?
	 */
	public $optional = FALSE;

	/**
	 * Creates a new Param
	 *
	 * @param type $name
	 * @param type $prop 
	 */
	public function __construct($name, $prop)
	{
		$this->param = new ReflectionParameter($name, $prop);

		$this->name = $this->param->name;

		if ($this->param->isDefaultValueAvailable())
		{
			$this->default = var_export($this->param->getDefaultValue(), true);
		}

		if ($this->param->isPassedByReference())
		{
			$this->reference = TRUE;
		}

		if ($this->param->isOptional())
		{
			$this->optional = TRUE;
		}
	}

	public function __toString()
	{
		$display = '';

		if ($this->type)
		{
			$display .= '<small>'.$this->type.'</small> ';
		}

		if ($this->reference)
		{
			$display .= '<small><abbr title="passed by reference">&</abbr></small> ';
		}

		if ($this->description)
		{
			$display .= '<span class="param" title="'.$this->description.'">$'.$this->name.'</span> ';
		}
		else
		{
			$display .= '$'.$this->name.' ';
		}

		if ($this->default)
		{
			$display .= '<small>= '.$this->default.'</small> ';
		}

		return $display;
	}

}
