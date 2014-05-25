<?php

/**
 * Test: Nette\DI\Compiler: nonshared services factories.
 */

use Nette\DI,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class Ipsum
{
}

class Lorem
{
}


$container = createContainer(new DI\Compiler, 'files/compiler.services.nonshared.neon');


Assert::true( $container->hasService('lorem') );
Assert::true( method_exists($container, 'createServiceLorem') );

$params = new ReflectionParameter(array($container, 'createServiceLorem'), 0);
Assert::same( 'foo', $params->getName() );
Assert::same( 'Ipsum', $params->getClass()->getName() );
Assert::false( $params->isDefaultValueAvailable() );

$params = new ReflectionParameter(array($container, 'createServiceLorem'), 1);
Assert::same( 'bar', $params->getName() );
Assert::false( $params->getDefaultValue() );
