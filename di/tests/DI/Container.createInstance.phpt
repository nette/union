<?php

/**
 * Test: Nette\DI\ContainerBuilder and Container: createInstance()
 */

use Nette\DI,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class Test
{
	public $container;

	function __construct(stdClass $obj, DI\Container $container)
	{
		$this->container = $container;
	}

	function method(stdClass $obj, DI\Container $container)
	{
		return isset($obj->prop);
	}

}


$builder = new DI\ContainerBuilder;
$builder->addDefinition('one')
	->setClass('stdClass');


$container = createContainer($builder);

$test = $container->createInstance('Test');
Assert::type( 'Test', $test );
Assert::same( $container, $test->container );
Assert::false( $container->callMethod(array($test, 'method')) );
Assert::true( $container->callMethod(array($test, 'method'), array((object) array('prop' => TRUE))) );
