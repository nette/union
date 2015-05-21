<?php

/**
 * Test: Nette\Application\Routers\Route with optional sequence and two parameters.
 */

use Nette\Application\Routers\Route,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Route.inc';


$route = new Route('[<one [a-z]+><two [0-9]+>]', [
	'one' => 'a',
	'two' => '1',
]);

testRouteIn($route, '/a1', 'querypresenter', [
	'one' => 'a',
	'two' => '1',
	'test' => 'testvalue',
], '/?test=testvalue&presenter=querypresenter');

testRouteIn($route, '/x1', 'querypresenter', [
	'one' => 'x',
	'two' => '1',
	'test' => 'testvalue',
], '/x1?test=testvalue&presenter=querypresenter');

testRouteIn($route, '/a2', 'querypresenter', [
	'one' => 'a',
	'two' => '2',
	'test' => 'testvalue',
], '/a2?test=testvalue&presenter=querypresenter');

testRouteIn($route, '/x2', 'querypresenter', [
	'one' => 'x',
	'two' => '2',
	'test' => 'testvalue',
], '/x2?test=testvalue&presenter=querypresenter');
