<?php
/**
 * Class method documentation generator.
 *
 * @package    Kohana/Userguide
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class LiveAPI_Method
{
	/**
	 * @var  ReflectionMethod   The ReflectionMethod for this class
	 */
	public $method;

	/**
	 * @var  array    array of Kodoc_Method_Param
	 */
	public $params;

	/**
	 * @var  array   the things this function can return
	 */
	public $return = array();

	/**
	 * @var  string  the source code for this function
	 */
	public $source;

	/**
	 * Creates a new Method class.
	 *
	 * @param string $class  The calling class
	 * @param string $method The method name
	 */
	public function __construct($class, $method)
	{
		$this->method = new ReflectionMethod($class, $method);

		$this->class = $parent = $this->method->getDeclaringClass();

		$this->modifiers = Reflection::getModifierNames($this->method->getModifiers());

		do
		{
			if ($parent->hasMethod($method) AND $comment = $parent->getMethod($method)->getDocComment())
			{
				// Found a description for this method
				break;
			}
		}
		while ($parent = $parent->getParentClass());

		list($this->description, $tags) = LiveAPI::parse($comment);

		if ($file = $this->class->getFileName())
		{
			$this->source = LiveAPI::source($file, $this->method->getStartLine(), $this->method->getEndLine());
		}

		if (isset($tags['param']))
		{
			$params = array();

			foreach ($this->method->getParameters() as $i => $param)
			{
				$param = new LiveAPI_Param(array($this->method->class, $this->method->name),$i);

				if (isset($tags['param'][$i]))
				{
					preg_match('/^(\S+)(?:\s*(?:\$'.$param->name.'\s*)?(.+))?$/', $tags['param'][$i], $matches);

					$param->type = $matches[1];

					if (isset($matches[2]))
					{
						$param->description = ucfirst($matches[2]);
					}
				}
				$params[] = $param;
			}

			$this->params = $params;

			unset($tags['param']);
		}

		if (isset($tags['return']))
		{
			foreach ($tags['return'] as $return)
			{
				if (preg_match('/^(\S*)(?:\s*(.+?))?$/', $return, $matches))
				{
					$this->return[] = array(
						'type' => $matches[1],
						'description' => isset($matches[2]) ? $matches[2] : ''
					);
				}
			}

			unset($tags['return']);
		}

		$this->tags = $tags;
	}

	/**
	 * Gets the name of this property.
	 *
	 * @return string
	 */
	public function name()
	{
		return $this->method->name;
	}

	/**
	 * Gets the modifiers for this property.
	 *
	 * @return string
	 */
	public function get_modifiers()
	{
		return implode(" ", $this->modifiers);
	}

	public function has_params()
	{
		return count($this->params) > 0;
	}

	public function has_tags()
	{
		return count($this->tags) > 0;
	}

	public function has_return()
	{
		return count($this->return) > 0;
	}

	/**
	 * Gets the tags into a parsable array
	 *
	 * @return  array
	 */
	public function get_tags()
	{
		$tags = array();
		foreach ($this->tags as $key => $value)
		{
			$tags[] = array(
				'name' => $key,
				'value' => implode(" - ", $value),
			);
		}

		return $tags;
	}

	public function param_list()
	{
		if (empty($this->params))
		{
			return "";
		}
		
		$out = ' ';
		$required = TRUE;
		$first = TRUE;
		foreach ($this->params as $param)
		{
			if ($required AND $param->default AND $first)
			{
				$out .= '[ '.$param;
				$required = FALSE;
				$first = FALSE;
			}
			elseif ($required AND $param->default)
			{
				$out .= '[, '.$param;
				$required = FALSE;
			}
			elseif ($first)
			{
				$out .= $param;
				$first = FALSE;
			}
			else
			{
				$out .= ', '.$param;
			}
		}

		if ( ! $required)
		{
			$out .= '] ';
		}

		return $out;
	}
}
