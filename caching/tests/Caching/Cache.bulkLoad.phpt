<?php

/**
 * Test: Nette\Caching\Cache load().
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Cache.php';

test('storage without bulk load support', function () {
	$storage = new TestStorage;
	$cache = new Cache($storage, 'ns');
	$cache->onEvent[] = function (...$args) use (&$event) {
		$event[] = $args;
	};

	Assert::same([1 => null, 2 => null], $cache->bulkLoad([1, 2]), 'data');
	Assert::same([[$cache, $cache::EVENT_MISS, 1], [$cache, $cache::EVENT_MISS, 2]], $event);

	$event = [];
	Assert::same([1 => 1, 2 => 2], $cache->bulkLoad([1, 2], function ($key) {
		return $key;
	}));
	Assert::same([
		[$cache, $cache::EVENT_MISS, 1], [$cache, $cache::EVENT_SAVE, 1],
		[$cache, $cache::EVENT_MISS, 2], [$cache, $cache::EVENT_SAVE, 2],
	], $event);

	$event = [];
	$data = $cache->bulkLoad([1, 2]);
	Assert::same(1, $data[1]['data']);
	Assert::same(2, $data[2]['data']);
	Assert::same([[$cache, $cache::EVENT_HIT, 1], [$cache, $cache::EVENT_HIT, 2]], $event);
});

test('storage with bulk load support', function () {
	$storage = new BulkReadTestStorage;
	$cache = new Cache($storage, 'ns');
	$cache->onEvent[] = function (...$args) use (&$event) {
		$event[] = $args;
	};

	Assert::same([1 => null, 2 => null], $cache->bulkLoad([1, 2]));
	Assert::same([[$cache, $cache::EVENT_MISS, 1], [$cache, $cache::EVENT_MISS, 2]], $event);

	$event = [];
	Assert::same([1 => 1, 2 => 2], $cache->bulkLoad([1, 2], function ($key) {
		return $key;
	}));
	Assert::same([
		[$cache, $cache::EVENT_MISS, 1], [$cache, $cache::EVENT_SAVE, 1],
		[$cache, $cache::EVENT_MISS, 2], [$cache, $cache::EVENT_SAVE, 2],
	], $event);

	$event = [];
	$data = $cache->bulkLoad([1, 2]);
	Assert::same(1, $data[1]['data']);
	Assert::same(2, $data[2]['data']);
	Assert::same([[$cache, $cache::EVENT_HIT, 1], [$cache, $cache::EVENT_HIT, 2]], $event);
});

test('dependencies', function () {
	$storage = new BulkReadTestStorage;
	$cache = new Cache($storage, 'ns');
	$dependencies = [Cache::TAGS => ['tag']];
	$cache->bulkLoad([1], function ($key, &$deps) use ($dependencies) {
		$deps = $dependencies;
		return $key;
	});

	$data = $cache->bulkLoad([1, 2]);
	Assert::same($dependencies, $data[1]['dependencies']);
});

test('', function () {
	Assert::exception(function () {
		$cache = new Cache(new BulkReadTestStorage);
		$cache->bulkLoad([[1]]);
	}, Nette\InvalidArgumentException::class, 'Only scalar keys are allowed in bulkLoad()');
});
