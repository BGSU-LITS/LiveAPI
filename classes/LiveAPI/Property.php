<?php
/**
 * Class property documentation generator.
 *
 * @package    Kohana/Userguide
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class LiveAPI_Property
{
	/**
	 * @var  object  ReflectionProperty
	 */
	public $property;

	/**
	 * @var  string   modifiers: public, private, static, etc
	 */
	public $modifiers = 'public';

	/**
	 * @var  string  variable type, retrieved from the comment
	 */
	public $type;

	/**
	 * @var  string  value of the property
	 */
	public $value;

	/**
	 * Creates a new Propery class
	 *
	 * @param string $class     The class name
	 * @param string $property  The property name
	 * @param mixed             The default value for the property
	 */
	public function __construct($class, $property, $value)
	{
		$property = new ReflectionProperty($class, $property);

		list($description, $tags) = LiveAPI::parse($property->getDocComment());

		$this->description = $description;
		$this->modifiers = Reflection::getModifierNames($property->getModifiers());

		if (isset($tags['var']))
		{
			if (preg_match('/^(\S*)(?:\s*(.+?))?$/', $tags['var'][0], $matches))
			{
				$this->type = $matches[1];

				if (isset($matches[2]))
				{
					$this->description = Markdown($matches[2]);
				}
			}
		}

		$this->property = $property;

		$this->value = ($value === null) ? null : var_export($value, true);
	}

	/**
	 * Sets the value of the
	 *
	 * @return string A string representation of the value
	 */
	public function has_value()
	{
		return $this->value !== null;
	}

	/**
	 * Gets the name of this property.
	 *
	 * @return string
	 */
	public function name()
	{
		return $this->property->name;
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
}
