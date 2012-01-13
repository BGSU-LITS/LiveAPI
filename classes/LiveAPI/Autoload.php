<?php
/**
 * Autoloading for the LiveAPI classes
 *
 * @package    LiveAPI
 * @author     Dave Widmer <dwidmer@bgsu.edu>
 */
class LiveAPI_Autoload
{
	/**
	 * @var string   The base path for loading
	 */
	private $include_path = "";

	/**
	 * @var string   The namespace
	 */
	private $namespace = "";

	/**
	 * @var string  The character that separates the namespaces
	 */
	private $separator = "\\";

	/**
	 * @var string  The file extension.
	 */
	private $extension = ".php";

	/**
	 * Gets the autoloader setup for use.
	 *
	 * @param string $path  The include path for the files
	 * @param string $ns    The namespace for the classes
	 */
	public function __construct($path, $ns = "")
	{
		$this->include_path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$this->namespace = $ns;
	}

	/**
	 * Class autoloading
	 *
	 * @param string $class  The class name
	 */
	public function autoload($class)
	{
		if ($this->namespace)
		{
			$pattern = '/^'.$this->namespace.$this->separator.'/i';
			$class = preg_replace($pattern, "", $class);
		}
		
		// Replace underscores and namespace delimiters
		$class = str_replace(array("_", $this->separator), DIRECTORY_SEPARATOR, $class);

		$file = $this->include_path.$class.$this->extension;

		if (is_file($file))
		{
			include $file;
		}
	}

	/**
	 * Registers the autoloader
	 */
	public function register()
	{
		spl_autoload_register(array($this, "autoload"));
	}

	/**
	 * Unregisters the autoloader
	 */
	public function unregister()
	{
		spl_autoload_unregister(array($this, "autoload"));
	}

	/**
	 * Unregisters the autoloading for this class when the object is removed.
	 */
	public function __destruct()
	{
		$this->unregister();
	}
} 
