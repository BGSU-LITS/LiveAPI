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
	 * @param type $class
	 * @param ReflectionProperty $property 
	 */
	public function __construct($class, $property)
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
		
		// Show the value of static properties, but only if they are public or we are php 5.3 or higher and can force them to be accessible
		if ($property->isStatic() AND ($property->isPublic() OR version_compare(PHP_VERSION, '5.3', '>=')))
		{
			// Force the property to be accessible
			if (version_compare(PHP_VERSION, '5.3', '>='))
			{
				$property->setAccessible(TRUE);
			}
			
			// Don't debug the entire object, just say what kind of object it is
			if (is_object($property->getValue($class)))
			{
				$this->value = '<object '.get_class($property->getValue($class)).'()';
			}
			else
			{
				$this->value = $property->getValue($class);
			}
		}
		
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
