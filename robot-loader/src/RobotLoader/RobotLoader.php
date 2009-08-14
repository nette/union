<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette\Loaders
 */

/*namespace Nette\Loaders;*/



require_once dirname(__FILE__) . '/../Loaders/AutoLoader.php';

require_once dirname(__FILE__) . '/../Framework.php';



/**
 * Nette auto loader is responsible for loading classes and interfaces.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Loaders
 */
class RobotLoader extends AutoLoader
{
	/** @var array */
	public $scanDirs;

	/** @var string  comma separated wildcards */
	public $ignoreDirs = '.*, *.old, *.bak, *.tmp';

	/** @var string  comma separated wildcards */
	public $acceptFiles = '*.php, *.php5';

	/** @var bool */
	public $autoRebuild;

	/** @var array */
	private $list = array();

	/** @var bool */
	private $rebuilded = FALSE;

	/** @var string */
	private $acceptMask;

	/** @var string */
	private $ignoreMask;



	/**
	 * Register autoloader.
	 * @return void
	 */
	public function register()
	{
		$cache = $this->getCache();
		$data = $cache['data'];
		if ($data['opt'] === array($this->scanDirs, $this->ignoreDirs, $this->acceptFiles)) {
			$this->list = $data['list'];
		} else {
			$this->rebuild();
		}

		if (isset($this->list[strtolower(__CLASS__)]) && class_exists(/*Nette\Loaders\*/'NetteLoader', FALSE)) {
			NetteLoader::getInstance()->unregister();
		}

		parent::register();
	}



	/**
	 * Handles autoloading of classes or interfaces.
	 * @param  string
	 * @return void
	 */
	public function tryLoad($type)
	{
		$type = strtolower($type);
		/*$type = ltrim($type, '\\'); // PHP namespace bug #49143 */
		if (isset($this->list[$type])) {
			if ($this->list[$type] !== FALSE) {
				LimitedScope::load($this->list[$type]);
				self::$count++;
			}

		} else {
			$this->list[$type] = FALSE;

			if ($this->autoRebuild === NULL) {
				$this->autoRebuild = !$this->isProduction();
			}

			if ($this->autoRebuild) {
				$this->rebuild(FALSE);
			}

			if ($this->list[$type] !== FALSE) {
				LimitedScope::load($this->list[$type]);
				self::$count++;
			}
		}
	}



	/**
	 * Rebuilds class list cache.
	 * @param  bool
	 * @return void
	 */
	public function rebuild($force = TRUE)
	{
		$this->acceptMask = self::wildcards2re($this->acceptFiles);
		$this->ignoreMask = self::wildcards2re($this->ignoreDirs);

		if ($force || !$this->rebuilded) {
			foreach (array_unique($this->scanDirs) as $dir) {
				$this->scanDirectory($dir);
			}
		}

		$this->rebuilded = TRUE;
		$cache = $this->getCache();
		$cache['data'] = array(
			'list' => $this->list,
			'opt' => array($this->scanDirs, $this->ignoreDirs, $this->acceptFiles),
		);
	}



	/**
	 * Add directory (or directories) to list.
	 * @param  string|array
	 * @return void
	 * @throws \DirectoryNotFoundException if path is not found
	 */
	public function addDirectory($path)
	{
		foreach ((array) $path as $val) {
			$real = realpath($val);
			if ($real === FALSE) {
				throw new /*\*/DirectoryNotFoundException("Directory '$val' not found.");
			}
			$this->scanDirs[] = $real;
		}
	}



	/**
	 * Add class and file name to the list.
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function addClass($class, $file)
	{
		$class = strtolower($class);
		if (!empty($this->list[$class]) && $this->list[$class] !== $file) {
			spl_autoload_call($class); // hack: enables exceptions
			throw new /*\*/InvalidStateException("Ambiguous class '$class' resolution; defined in $file and in " . $this->list[$class] . ".");
		}
		$this->list[$class] = $file;
	}



	/**
	 * Scan a directory for PHP files, subdirectories and 'netterobots.txt' file.
	 * @param  string
	 * @return void
	 */
	private function scanDirectory($dir)
	{
		$dir = realpath($dir);
		if (!$dir) return;
		$iterator = dir($dir);
		if (!$iterator) return;

		$disallow = array();
		if (is_file($dir . '/netterobots.txt')) {
			foreach (file($dir . '/netterobots.txt') as $s) {
				if (preg_match('#^disallow\\s*:\\s*(\\S+)#i', $s, $m)) {
					$disallow[trim($m[1], '/')] = TRUE;
				}
			}
			if (isset($disallow[''])) return;
		}

		while (FALSE !== ($entry = $iterator->read())) {
			if ($entry == '.' || $entry == '..' || isset($disallow[$entry])) continue;

			$path = $dir . '/' . $entry;
			if (!is_readable($path)) continue;

			// process subdirectories
			if (is_dir($path)) {
				// check ignore mask
				if (!preg_match($this->ignoreMask, $entry)) {
					$this->scanDirectory($path);
				}
				continue;
			}

			if (is_file($path) && preg_match($this->acceptMask, $entry)) {
				$this->scanScript($path);
			}
		}

		$iterator->close();
	}



	/**
	 * Analyse PHP file.
	 * @param  string
	 * @return void
	 */
	private function scanScript($file)
	{
		if (!defined('T_NAMESPACE')) {
			define('T_NAMESPACE', -1);
			define('T_NS_SEPARATOR', -1);
		}

		$expected = FALSE;
		$namespace = '';
		$level = 0;
		$s = file_get_contents($file);

		if (preg_match('#//nette'.'loader=(\S*)#', $s, $matches)) {
			foreach (explode(',', $matches[1]) as $name) {
				$this->addClass($name, $file);
			}
			return;
		}

		foreach (token_get_all($s) as $token)
		{
			if (is_array($token)) {
				switch ($token[0]) {
				case T_NAMESPACE:
				case T_CLASS:
				case T_INTERFACE:
					$expected = $token[0];
					$name = '';
					continue 2;

				case T_COMMENT:
				case T_DOC_COMMENT:
				case T_WHITESPACE:
					continue 2;

				case T_NS_SEPARATOR:
				case T_STRING:
					if ($expected) {
						$name .= $token[1];
					}
					continue 2;
				}
			}

			if ($expected) {
				if ($expected === T_NAMESPACE) {
					$namespace = $name . '\\';
				} elseif ($level === 0) {
					$this->addClass($namespace . $name, $file);
				}
				$expected = FALSE;
			}

			if (is_array($token)) {
				if ($token[0] === T_CURLY_OPEN || $token[0] === T_DOLLAR_OPEN_CURLY_BRACES) {
					$level++;
				}
			} elseif ($token === '{') {
				$level++;
			} elseif ($token === '}') {
				$level--;
			}
		}
	}



	/**
	 * Converts comma separated wildcards to regular expression.
	 * @param  string
	 * @return string
	 */
	private static function wildcards2re($wildcards)
	{
		$mask = array();
		foreach (explode(',', $wildcards) as $wildcard) {
			$wildcard = trim($wildcard);
			$wildcard = addcslashes($wildcard, '.\\+[^]$(){}=!><|:#');
			$wildcard = strtr($wildcard, array('*' => '.*', '?' => '.'));
			$mask[] = $wildcard;
		}
		return '#^(' . implode('|', $mask) . ')$#i';
	}



	/********************* backend ****************d*g**/



	/**
	 * @return Nette\Caching\Cache
	 */
	protected function getCache()
	{
		return /*Nette\*/Environment::getCache('Nette.RobotLoader');
	}



	/**
	 * @return bool
	 */
	protected function isProduction()
	{
		return /*Nette\*/Environment::isProduction();
	}

}
