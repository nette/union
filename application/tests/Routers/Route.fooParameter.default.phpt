<?php

/**
 * Test: Nette\Application\Routers\Route with FooParameter
 */

declare(strict_types=1);

use Nette\Application\Routers\Route;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Route.php';


$route = new Route('index<?.xml \.html?|\.php|>/', [
	'presenter' => 'DefaultPresenter',
]);

testRouteIn($route, '/index.');

testRouteIn($route, '/index.xml', 'DefaultPresenter', [
	'test' => 'testvalue',
], '/index.xml/?test=testvalue');

testRouteIn($route, '/index.php', 'DefaultPresenter', [
	'test' => 'testvalue',
], '/index.xml/?test=testvalue');

testRouteIn($route, '/index.htm', 'DefaultPresenter', [
	'test' => 'testvalue',
], '/index.xml/?test=testvalue');

testRouteIn($route, '/index', 'DefaultPresenter', [
	'test' => 'testvalue',
], '/index.xml/?test=testvalue');
