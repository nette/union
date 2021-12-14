<?php

/**
 * Test: Nette\DI\Resolver::autowireArguments()
 */

declare(strict_types=1);

use Nette\DI\Resolver;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


// unknown
Assert::exception(function () {
	Resolver::autowireArguments(
		new ReflectionFunction(function (stdClass $x) {}),
		[],
		function () {}
	);
}, Nette\DI\ServiceCreationException::class, 'Service of type stdClass required by $x in {closure}%a?% not found. Did you add it to configuration file?');


// not found
Assert::exception(function () {
	Resolver::autowireArguments(
		new ReflectionFunction(function (Foo $x) {}),
		[],
		function () {}
	);
}, Nette\DI\ServiceCreationException::class, "Class 'Foo' required by \$x in {closure}%a?% not found. Check the parameter type and 'use' statements.");


// no typehint
Assert::exception(function () {
	Resolver::autowireArguments(
		new ReflectionFunction(function ($x) {}),
		[],
		function () {}
	);
}, Nette\DI\ServiceCreationException::class, 'Parameter $x in {closure}%a?% has no class type or default value, so its value must be specified.');


// scalar
Assert::exception(function () {
	Resolver::autowireArguments(
		new ReflectionFunction(function (int $x) {}),
		[],
		function () {}
	);
}, Nette\DI\ServiceCreationException::class, 'Parameter $x in {closure}%a?% has no class type or default value, so its value must be specified.');


// variadics + array
Assert::exception(function () {
	Resolver::autowireArguments(
		new ReflectionFunction(function (...$args) {}),
		['args' => []],
		function () {}
	);
}, Nette\DI\ServiceCreationException::class, 'Unable to pass specified arguments to {closure}%a?%.');
