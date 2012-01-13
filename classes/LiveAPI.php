<?php
/**
 * The LiveAPI class.
 *
 * @package    LiveAPI
 * @author     Dave Widmer <dwidmer@bgsu.edu>
 */
class LiveAPI
{
	/**
	 * @var string The base path to run all documentation operations on
	 */
	private $path = null;

	/**
	 * @var array  The classes to parse.
	 */
	private $classes = null;

	/**
	 * @var Mustache  The mustache renderer.
	 */
	private $mustatche = null;

	/**
	 * @var string The mustache template to render a class
	 */
	private $template = "";

	/**
	 * @var array  The configuration for building the api docs
	 */
	protected $config = array(
		'dir' => "classes",
		'output' => "api",
		'namespace' => "",
		'separator' => "\\",
		'theme' => "default",
		'title' => "LiveAPI",
	);

	/**
	 * Setup a new LiveAPI class for api generation
	 *
	 * @param array $argv  The command-line arguments
	 */
	public function __construct(array $argv)
	{
		// Set the base path
		$this->path = exec("pwd").DIRECTORY_SEPARATOR;

		array_shift($argv); // Take off the script name

		$config = array();
		// Get the arguments
		foreach ($argv as $arg)
		{
			$arg = ltrim($arg, "--");
			list($key, $value) = explode("=", $arg);
			$config[$key] = $value;
		}

		$this->config = array_merge($this->config, $config);

		// Set the full path for the file directory to search
		$this->config['dir'] = $this->path.rtrim($this->config['dir'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		// Normalize the namespace
		if ($this->config['namespace'])
		{
			$this->config['namespace'] = rtrim($this->config['namespace'], $this->config['separator']) . $this->config['separator'];
		}

		// Convert output path to full path
		if (substr($this->config['output'], 0, 1) !== DIRECTORY_SEPARATOR)
		{
			$this->config['output'] = $this->path.rtrim($this->config['output'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		}
	}

	/**
	 * Run the docs. This is where all the magic happens!!
	 */
	public function run()
	{
		$theme_path = LIVEAPI_PATH."themes".DIRECTORY_SEPARATOR.$this->config['theme'].DIRECTORY_SEPARATOR;

		// Grab the template
		$this->mustache = new Mustache;
		$this->template = file_get_contents($theme_path."class.mustache");

		// Create output directory if it doesn't exist
		if ( ! is_dir($this->config['output']))
		{
			if ( ! mkdir($this->config['output']))
			{
				throw new Exception("{$this->config['output']} is not writable.");
			}
		}

		// Save all of the files
		$files = array();
		foreach ($this->getClasses() as $class)
		{
			$files[] = $this->saveClass($class);
		}

		// Now generate the index page
		$template = file_get_contents($theme_path."template.mustache");
		$output = $this->mustache->render($template, array(
			'classes' => $files,
			'date' => date("F jS, Y"),
			'title' => $this->config['title']
		));

		$this->doSave("index", $output);

		// And move over the assets
		$this->copy_recursive($theme_path."assets", $this->config['output']);

		// And we are done!!
		echo "API Generated!\n";
	}

	/**
	 * Processes and saves the api doc for the class.
	 *
	 * @param  LiveAPI_Class $class The class to parse and save
	 * @return string  The filename that was saved
	 */
	private function saveClass(LiveAPI_Class $class)
	{
		$output = $this->mustache->render($this->template, $class);
		$filename = str_replace("\\", "_", $class->name());

		$this->doSave($filename, $output);

		return array(
			'name' => $class->name(),
			'file' => $filename.'.html'
		);
	}

	/**
	 * The actual file save mechanism.
	 *
	 * @param string $filename The file name
	 * @param string $output   The html output
	 */
	private function doSave($filename, $output)
	{
		$file = $this->config['output'].$filename.".html";
		$fp = fopen($file, "w+");
		fwrite($fp, $output);
		fclose($fp);
	}

	/**
	 * Copies files over recursively.
	 * 
	 * @todo  Rewrite to not use a shell command....
	 * @param string $from The source directory
	 * @param string $to   The destination directory
	 */
	private function copy_recursive($from, $to)
	{
		exec("`cp -r {$from} {$to}`");
	}

	/**
	 * Gets all of the classes in the project
	 *
	 * @return array (of LiveAPI_Class instances)
	 */
	public function getClasses()
	{
		if ($this->classes === null)
		{
			$dir = new RecursiveDirectoryIterator($this->config['dir']);
			$iterator = new RecursiveIteratorIterator($dir);
			$regex = new RegexIterator($iterator, '/^.+\.php$/i');

			foreach ($regex as $row)
			{
				$pathname = $row->getPathname();

				// Make a key that is the fully qualified name of the class
				$name = $this->config['namespace'] . str_replace(
						array($this->config['dir'], DIRECTORY_SEPARATOR),
						array("", $this->config['separator']),
						substr($pathname, 0, -4)
				);

				$this->classes[] = new LiveAPI_Class($name, $pathname);
			}
		}

		return $this->classes;
	}

	/**
	 * Gets the configuration data.
	 *
	 * @param  string $key  The key to get, if no key is set, the whole config array will be returned
	 * @return mixed        The value of the key, or the full config array
	 */
	public function getConfig($key = null)
	{
		if ($key === null)
		{
			return $this->config;
		}

		return isset($this->config[$key]) ? $this->config[$key] : null;
	}

	/**
	 * Parse a comment to extract the description and the tags
	 *
	 * @param   string  the comment retreived using ReflectionClass->getDocComment()
	 * @return  array   array(string $description, array $tags)
	 */
	public static function parse($comment)
	{
		// Normalize all new lines to \n
		$comment = str_replace(array("\r\n", "\n"), "\n", $comment);

		// Remove the phpdoc open/close tags and split
		$comment = array_slice(explode("\n", $comment), 1, -1);

		// Tag content
		$tags = array();

		foreach ($comment as $i => $line)
		{
			// Remove all leading whitespace
			$line = preg_replace('/^\s*\* ?/m', '', $line);

			// Search this line for a tag
			if (preg_match('/^@(\S+)(?:\s*(.+))?$/', $line, $matches))
			{
				// This is a tag line
				unset($comment[$i]);

				$name = $matches[1];
				$text = isset($matches[2]) ? $matches[2] : '';

				switch ($name)
				{
					case 'license':
						if (strpos($text, '://') !== FALSE)
						{
							// Convert the license into a link
							$text = '<a href="'.$text.'">'.$text.'</a>';
						}
					break;
					case 'link':
					case 'see':
						$text = preg_split('/\s+/', $text, 2);
						$desc = isset($text[1]) ? $text[1] : $text[0];
						$text = '<a href="'.$text.'">'.$desc.'</a>';
					break;
					case 'copyright':
						if (strpos($text, '(c)') !== FALSE)
						{
							// Convert the copyright sign
							$text = str_replace('(c)', '&copy;', $text);
						}
					break;
					case 'throws':
						/** Figure out a good way to do this...
						if (preg_match('/^(\w+)\W(.*)$/', $text, $matches))
						{
							$text = HTML::anchor(Route::get('docs/api')->uri(array('class' => $matches[1])), $matches[1]).' '.$matches[2];
						}
						else
						{
							$text = HTML::anchor(Route::get('docs/api')->uri(array('class' => $text)), $text);
						}
						 */
					break;
					case 'uses':
						/** Figure out a good way for this too...
						if (preg_match('/^'.Kodoc::$regex_class_member.'$/i', $text, $matches))
						{
							$text = Kodoc::link_class_member($matches);
						}
						 */
					break;
					// Don't show @access lines, they are shown elsewhere
					case 'access':
						continue 2;
				}

				// Add the tag
				$tags[$name][] = $text;
			}
			else
			{
				// Overwrite the comment line
				$comment[$i] = (string) $line;
			}
		}

		// Concat the comment lines back to a block of text
		if ($comment = trim(implode("\n", $comment)))
		{
			// Parse the comment with Markdown
			$comment = Markdown($comment);
		}

		return array($comment, $tags);
	}

	/**
	 * Get the source of a function
	 *
	 * @param  string   the filename
	 * @param  int      start line?
	 * @param  int      end line?
	 */
	public static function source($file, $start, $end)
	{
		if ( ! $file) return FALSE;

		$file = file($file, FILE_IGNORE_NEW_LINES);

		$file = array_slice($file, $start - 1, $end - $start + 1);

		if (preg_match('/^(\s+)/', $file[0], $matches))
		{
			$padding = strlen($matches[1]);

			foreach ($file as & $line)
			{
				$line = substr($line, $padding);
			}
		}

		return implode("\n", $file);
	}

}
