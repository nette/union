<?php

/**
 * Test: Nette\DI\Container magic properties (deprecated).
 */

use Nette\DI\Container;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class Service
{
}

$one = new Service;

$container = new Container(['container' => ['accessors' => TRUE]]);
$container->one = $one;

Assert::true(isset($container->one));
Assert::same($one, $container->one);

Assert::false(isset($container->undefined));


Assert::error(function () {
	$container = new Container;
	$container->one = new Service;
}, E_USER_DEPRECATED, 'Nette\DI\Container::__set() is deprecated; use addService() or enable di.accessors in configuration.');
