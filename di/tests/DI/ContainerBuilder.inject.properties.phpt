<?php

/**
 * Test: Nette\DI\ContainerBuilder and inject properties.
 */

use Nette\DI,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class Test1
{
	/** @inject @var stdClass */
	public $varA;

	/** @var ReflectionClass @inject */
	public $varX;

}

class Test2 extends Test1
{
	/** @var stdClass @inject */
	public $varB;
}


$builder = new DI\ContainerBuilder;
$builder->addDefinition('test')
	->setInject(TRUE)
	->setClass('Test2')
	->addSetup('$varX', array(123));

$builder->addDefinition('stdClass')
	->setClass('stdClass');


$container = createContainer($builder);

$test = $container->getService('test');
Assert::type( 'Test1', $test );
Assert::type( 'stdClass', $test->varA );
Assert::type( 'stdClass', $test->varB );
Assert::same( $test->varX, 123 );
