<?php

/**
 * Test: Nette\Config\Configurator and services inheritance and overwriting.
 *
 * @author     David Grudl
 * @package    Nette\Config
 * @subpackage UnitTests
 */

use Nette\Config\Configurator;



require __DIR__ . '/../bootstrap.php';



class MyApp extends Nette\Application\Application
{
}



$configurator = new Configurator;
$configurator->addParameters(array(
	'productionMode' => TRUE,
));
$configurator->setCacheDirectory(TEMP_DIR);
$container = $configurator->loadConfig('files/config.inheritance2.neon', FALSE);


Assert::true( $container->application instanceof MyApp );
Assert::null( $container->application->catchExceptions );
Assert::same( 'Error', $container->application->errorPresenter );

Assert::true( $container->app2 instanceof MyApp );
Assert::null( $container->app2->catchExceptions );
Assert::null( $container->app2->errorPresenter );
