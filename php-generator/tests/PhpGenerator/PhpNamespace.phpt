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

$interfaceB = $namespace->addInterface('B');
Assert::same($namespace, $interfaceB->getNamespace());

Assert::count(2, $namespace->getClasses());
Assert::type(Nette\PhpGenerator\ClassType::class, $namespace->getClasses()['A']);
$namespace->removeClass('a');
Assert::count(1, $namespace->getClasses());

$function = $namespace->addFunction('foo');
Assert::count(1, $namespace->getFunctions());
Assert::same($function, $namespace->getFunctions()['foo']);
$namespace->removeFunction('FOO');
Assert::count(0, $namespace->getFunctions());
