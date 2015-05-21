<?php

/**
 * Test: Nette\Caching\Storages\MemcachedStorage sliding expiration test.
 */

use Nette\Caching\Storages\MemcachedStorage,
	Nette\Caching\Cache,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


if (!MemcachedStorage::isAvailable()) {
	Tester\Environment::skip('Requires PHP extension Memcache.');
}


$key = 'nette-sliding-key';
$value = 'rulez';

$cache = new Cache(new MemcachedStorage('localhost'));


// Writing cache...
$cache->save($key, $value, [
	Cache::EXPIRATION => time() + 3,
	Cache::SLIDING => TRUE,
]);


for ($i = 0; $i < 5; $i++) {
	// Sleeping 1 second
	sleep(1);

	Assert::truthy( $cache->load($key) );

}

// Sleeping few seconds...
sleep(5);

Assert::null( $cache->load($key) );
