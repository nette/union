<?php

/**
 * Test: Nette\Caching\Storages\FileStorage wrap().
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


function mockFunction($x, $y)
{
	$GLOBALS['called'] = TRUE;
	return $x + $y;
}


class Test
{
	function mockMethod($x, $y)
	{
		$GLOBALS['called'] = TRUE;
		return $x + $y;
	}
}


$cache = new Cache(new FileStorage(TEMP_DIR));

$called = FALSE;
Assert::same(55, $cache->wrap('mockFunction')(5, 50));
Assert::true($called);

$called = FALSE;
Assert::same(55, $cache->wrap('mockFunction')(5, 50));
Assert::false($called);


$called = FALSE;
$callback = [new Test, 'mockMethod'];
Assert::same(55, $cache->wrap($callback)(5, 50));
Assert::true($called);

$called = FALSE;
Assert::same(55, $cache->wrap($callback)(5, 50));
Assert::false($called);
