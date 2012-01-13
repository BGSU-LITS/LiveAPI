<?php
/**
 * A LiveAPI Class object contains the reflection information for a given class
 *
 * A lot of this functionality was taken from the Userguide module from the
 * Kohana Framework
 * 
 * @link       https://github.com/kohana/userguide
 *
 * @package    LiveAPI
 * @author     Dave Widmer <dwidmer@bgsu.edu>
 */
class LiveAPI_Class
{
	/**
	 * @var ReflectionClass  The reflected version of the class
	 */
	public $class;

	/**
	 * @var  string  modifiers like abstract, final
	 */
	public $modifiers;

	/**
	 * @var  string  description of the entity from the comment
	 */
	public $description;

	/**
	 * @var  array  array of tags, retrieved from the comment
	 */
	public $tags = array();

	/**
	 * @var string  The full path to the file
	 */
	public $path;

	/**
	 * @var  array  array of this classes constants
	 */
	public $constants = array();

	/**
	 * @var  array  array of this classes properties
	 */
	public $properties = array();

	/**
	 * @var  array  array of this methods
	 */
	public $methods = array();

	/**
	 * Sets up an Class for reflection.
	 *
	 * @param string $class  The fully qualified class name
	 * @param string $path   The full path to the class
	 */
	public function __construct($class, $path)
	{
		$this->class = new ReflectionClass($class);
		$this->path = $path;

		list($this->description, $this->tags) = LiveAPI::parse($this->class->getDocComment());

		$this->modifiers = Reflection::getModifierNames($this->class->getModifiers());
		foreach ($this->class->getConstants() as $key => $value)
		{
			$this->constants[] = array(
				'name' => $key,
				'value' => var_export($value, true),
				'type' => gettype($value),
			);
		}

		$props = $this->class->getProperties();
		usort($props, array($this, "_prop_sort"));
		foreach ($props as $prop)
		{
			$this->properties[] = new LiveAPI_Property($class, $prop->name);
		}

		$methods = $this->class->getMethods();
		usort($methods, array($this, '_method_sort'));
		foreach ($methods as $m)
		{
			$this->methods[] = new LiveAPI_Method($class, $m->name);
		}
	}

	/**
	 * Gets the name of the class
	 *
	 * @return string
	 */
	public function name()
	{
		return $this->class->name;
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

	/**
	 * Checks to see if this class has contants
	 *
	 * @return boolean
	 */
	public function has_constants()
	{
		return count($this->constants) > 0;
	}

	/**
	 * Checks to see if this class has properties
	 *
	 * @return boolean
	 */
	public function has_properties()
	{
		return count($this->properties) > 0;
	}

	/**
	 * Checks to see if this class has methods
	 *
	 * @return boolean
	 */
	public function has_methods()
	{
		return count($this->methods) > 0;
	}

	/**
	 * A custom sorting method for the class properties.
	 *
	 * @param  ReflectionProperty $a The first item to compare
	 * @param  ReflectionProperty $b The second item to compare
	 * @return int                   -1 for up, 1 for down
	 */
	protected function _prop_sort($a, $b)
	{
		// If one property is public, and the other is not, it goes on top
		if ($a->isPublic() AND ( ! $b->isPublic()))
			return -1;
		if ($b->isPublic() AND ( ! $a->isPublic()))
			return 1;
		
		// If one property is protected and the other is private, it goes on top
		if ($a->isProtected() AND $b->isPrivate())
			return -1;
		if ($b->isProtected() AND $a->isPrivate())
			return 1;
		
		// Otherwise just do alphabetical
		return strcmp($a->name, $b->name);
	}
	
	/**
	 * Sort methods based on their visibility and declaring class based on:
	 *  - methods will be sorted public, protected, then private.
	 *  - methods that are declared by an ancestor will be after classes
	 *    declared by the current class
	 *  - lastly, they will be sorted alphabetically
	 *
	 * @param  ReflectionMethod $a The first item to compare
	 * @param  ReflectionMethod $b The second item to compare
	 * @return int                 -1 for up, 1 for down
	 */
	protected function _method_sort($a, $b)
	{
		// If one method is public, and the other is not, it goes on top
		if ($a->isPublic() AND ( ! $b->isPublic()))
			return -1;
		if ($b->isPublic() AND ( ! $a->isPublic()))
			return 1;
		
		// If one method is protected and the other is private, it goes on top
		if ($a->isProtected() AND $b->isPrivate())
			return -1;
		if ($b->isProtected() AND $a->isPrivate())
			return 1;
		
		// The methods have the same visibility, so check the declaring class depth:
		
		
		/*
		echo kohana::debug('a is '.$a->class.'::'.$a->name,'b is '.$b->class.'::'.$b->name,
						   'are the classes the same?', $a->class == $b->class,'if they are, the result is:',strcmp($a->name, $b->name),
						   'is a this class?', $a->name == $this->class->name,-1,
						   'is b this class?', $b->name == $this->class->name,1,
						   'otherwise, the result is:',strcmp($a->class, $b->class)
						   );
		*/

		// If both methods are defined in the same class, just compare the method names
		if ($a->class == $b->class)
			return strcmp($a->name, $b->name);

		// If one of them was declared by this class, it needs to be on top
		if ($a->name == $this->class->name)
			return -1;
		if ($b->name == $this->class->name)
			return 1;

		// Otherwise, get the parents of each methods declaring class, then compare which function has more "ancestors"
		$adepth = 0;
		$bdepth = 0;

		$parent = $a->getDeclaringClass();
		do
		{
			$adepth++;
		}
		while ($parent = $parent->getParentClass());

		$parent = $b->getDeclaringClass();
		do
		{
			$bdepth++;
		}
		while ($parent = $parent->getParentClass());

		return $bdepth - $adepth;
	}

} 
