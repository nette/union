<?php

/**
 * Test: Nette\Caching\Cache save().
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Cache.php';


// save value with dependencies
$storage = new testStorage;
$cache = new Cache($storage, 'ns');
$dependencies = [Cache::TAGS => ['tag']];

$cache->save('key', 'value', $dependencies);

$res = $cache->load('key');
Assert::same('value', $res['data']);
Assert::same($dependencies, $res['dependencies']);
