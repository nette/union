<?php

/**
 * Test: Nette\Caching\Storages\SQLiteStorage expiration test.
 */

use Nette\Caching\Cache,
	Nette\Caching\Storages\SQLiteStorage,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!extension_loaded('pdo_sqlite')) {
	Tester\Environment::skip('Requires PHP extension pdo_sqlite.');
}


$key = 'nette';
$value = 'rulez';

$cache = new Cache(new SQLiteStorage);


// Writing cache...
$cache->save($key, $value, array(
	Cache::EXPIRATION => time() + 3,
));


// Sleeping 1 second
sleep(1);
clearstatcache();
Assert::truthy( $cache->load($key) );


// Sleeping 3 seconds
sleep(3);
clearstatcache();
Assert::null( $cache->load($key) );
