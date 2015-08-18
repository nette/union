<?php

/**
 * Test: Nette\PhpGenerator - PHP7 return type declarations
 * @phpversion 7.0
 */

namespace A
{
	class Foo {}
}

namespace
{
	use Nette\PhpGenerator\Method;
	use Tester\Assert;


	require __DIR__ . '/../bootstrap.php';

	// test from

	interface A
	{
		function testClass() : \A\Foo;
		function testScalar() : string;
	}

	$method = Method::from(A::class .'::testClass');
	Assert::same('\A\Foo', $method->getReturnType());

	$method = Method::from(A::class .'::testScalar');
	Assert::same('string', $method->getReturnType());

	// generating methods with return type declarations

	$method = (new Method)
		->setName('create')
		->setReturnType('Foo')
		->setBody('return new Foo();');

	Assert::match(
		'function create(): Foo
{
	return new Foo();
}
', (string) $method);

}
