<?php

/**
 * Test: Nette\Reflection\Parameter tests.
 */

use Nette\Reflection;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	function myFunction($test, $test2 = NULL)
	{
		echo $test;
	}

	$reflect = new Reflection\GlobalFunction('myFunction');
	$params = $reflect->getParameters();
	Assert::same(2, count($params));
	Assert::same('myFunction()', (string) $params[0]->getDeclaringFunction());
	Assert::null($params[0]->getClass());
	Assert::null($params[0]->getDeclaringClass());
	Assert::same('myFunction()', (string) $params[1]->getDeclaringFunction());
	Assert::null($params[1]->getClass());
	Assert::null($params[1]->getDeclaringClass());
});


test(function () {
	class Foo
	{
		function myMethod($test, $test2 = NULL)
		{
			echo $test;
		}
	}

	$reflect = new Reflection\ClassType('Foo');
	$params = $reflect->getMethod('myMethod')->getParameters();
	Assert::same(2, count($params));
	Assert::same('Foo::myMethod()', (string) $params[0]->getDeclaringFunction());
	Assert::null($params[0]->getClass());
	Assert::same('Foo', (string) $params[0]->getDeclaringClass());
	Assert::same('Foo::myMethod()', (string) $params[1]->getDeclaringFunction());
	Assert::null($params[1]->getClass());
	Assert::same('Foo', (string) $params[1]->getDeclaringClass());
});
