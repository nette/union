<?php

/**
 * Test: Nette\Loaders\RobotLoader basic usage.
 *
 * @author     David Grudl
 * @package    Nette\Loaders
 * @subpackage UnitTests
 */

use Nette\Loaders\RobotLoader,
	Nette\Environment;



require __DIR__ . '/../bootstrap.php';



// temporary directory
define('TEMP_DIR', __DIR__ . '/tmp');
TestHelpers::purge(TEMP_DIR);
Environment::setVariable('tempDir', TEMP_DIR);


$loader = new RobotLoader;
$loader->addDirectory('../../Nette/');
$loader->addDirectory(__DIR__);
$loader->addDirectory(__DIR__); // purposely doubled
$loader->register();

Assert::false( class_exists('ConditionalClass') );
Assert::true( class_exists('TestClassA') );
Assert::true( class_exists('MySpace1\TestClassB') );
Assert::true( class_exists('MySpace2\TestClassC') );
