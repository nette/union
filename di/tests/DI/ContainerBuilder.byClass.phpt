<?php

/**
 * Test: Nette\DI\ContainerBuilder code generator.
 */

use Nette\DI,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


interface IFactory
{
	static function create();
}

class Factory implements IFactory
{
	public static $methods;

	static function create()
	{
		self::$methods[] = array(__FUNCTION__, func_get_args());
		return new stdClass;
	}

}

class AnnotatedFactory
{
	public $methods;

	/** @return stdClass */
	function create()
	{
		$this->methods[] = array(__FUNCTION__, func_get_args());
		return new stdClass;
	}

}


class UninstantiableFactory
{
	public static function getInstance()
	{
		return new self;
	}

	private function __construct()
	{}

	/** @return stdClass */
	function create()
	{}
}


$builder = new DI\ContainerBuilder;
$builder->addDefinition('factory')
	->setClass('Factory');

$builder->addDefinition('annotatedFactory')
	->setClass('AnnotatedFactory');

$builder->addDefinition('two')
	->setClass('stdClass')
	->setAutowired(FALSE)
	->setFactory('@factory::create', array('@\Factory'))
	->addSetup(array('@\Factory', 'create'), array('@\Factory'));

$builder->addDefinition('three')
	->setClass('stdClass')
	->setAutowired(FALSE)
	->setFactory('@\Factory::create', array('@\Factory'));

$builder->addDefinition('four')
	->setAutowired(FALSE)
	->setFactory('@\AnnotatedFactory::create');

$builder->addDefinition('five')
	->setAutowired(FALSE)
	->setFactory('@\IFactory::create');

$builder->addDefinition('uninstantiableFactory')
	->setClass('UninstantiableFactory')
	->setFactory('UninstantiableFactory::getInstance');

$builder->addDefinition('six')
	->setAutowired(FALSE)
	->setFactory('@\UninstantiableFactory::create');



$container = createContainer($builder);

$factory = $container->getService('factory');
Assert::type( 'Factory', $factory );

Assert::type( 'stdClass', $container->getService('two') );
Assert::same(array(
	array('create', array($factory)),
	array('create', array($factory)),
), Factory::$methods);

Factory::$methods = NULL;

Assert::type( 'stdClass', $container->getService('three') );
Assert::same(array(
	array('create', array($factory)),
), Factory::$methods);

$annotatedFactory = $container->getService('annotatedFactory');
Assert::type( 'AnnotatedFactory', $annotatedFactory );

Assert::type( 'stdClass', $container->getService('four') );
Assert::same(array(
	array('create', array()),
), $annotatedFactory->methods);

Assert::type( 'stdClass', $container->getService('five') );
