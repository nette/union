<?php

/**
 * Test: Nette\Config\Configurator and autowiring.
 *
 * @author     David Grudl
 * @package    Nette\Config
 * @subpackage UnitTests
 */

use Nette\Config\Configurator;



require __DIR__ . '/../bootstrap.php';



class Factory
{
	/** @return Model  auto-wiring using annotation */
	static function createModel()
	{
		return new Model;
	}
}


class Model
{
	/** autowiring using parameters */
	function test(Lorem $arg)
	{
		TestHelpers::note(__METHOD__);
	}
}


class Lorem
{
	/** autowiring using parameters */
	static function test(Nette\Database\Connection $arg)
	{
		TestHelpers::note(__METHOD__);
	}
}


$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$container = $configurator->addConfig('files/config.autowiring.neon', Configurator::NONE)
	->createContainer();

Assert::true( $container->model instanceof Model );

Assert::same(array(
	'Model::test',
	'Model::test',
	'Model::test',
	'Lorem::test',
	'Lorem::test',
), TestHelpers::fetchNotes());
