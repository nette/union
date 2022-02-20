<?php

declare(strict_types=1);

use Nette\PhpGenerator\PhpNamespace;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$namespace = new PhpNamespace('');
Assert::same('', $namespace->getName());

$namespace = new PhpNamespace('Foo');
Assert::same('Foo', $namespace->getName());

$classA = $namespace->addClass('A');
Assert::same($namespace, $classA->getNamespace());

Assert::exception(function () use ($namespace) {
	$namespace->addClass('a');
}, Nette\InvalidStateException::class, "Cannot add 'a', because it already exists.");

$interfaceB = $namespace->addInterface('B');
Assert::same($namespace, $interfaceB->getNamespace());

Assert::count(2, $namespace->getClasses());
Assert::type(Nette\PhpGenerator\ClassType::class, $namespace->getClasses()['A']);
$namespace->removeClass('a');
Assert::count(1, $namespace->getClasses());


$function = $namespace->addFunction('foo');

Assert::exception(function () use ($namespace) {
	$namespace->addFunction('Foo');
}, Nette\InvalidStateException::class, "Cannot add 'Foo', because it already exists.");

Assert::count(1, $namespace->getFunctions());
Assert::same($function, $namespace->getFunctions()['foo']);
$namespace->removeFunction('FOO');
Assert::count(0, $namespace->getFunctions());
