<?php

/**
 * Test: Nette\Application\Routers\Route with FILTER_IN & FILTER_OUT
 */

declare(strict_types=1);

use Nette\Application\Routers\Route;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Route.php';


$route = new Route('<presenter>', [
	'presenter' => [
		Route::FILTER_IN => fn ($s) => strrev($s),
		Route::FILTER_OUT => fn ($s) => strrev($s),
	],
]);

testRouteIn($route, '/abc/', [
	'presenter' => 'cba',
	'test' => 'testvalue',
], '/abc?test=testvalue');
