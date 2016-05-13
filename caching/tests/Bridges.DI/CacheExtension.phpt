<?php

/**
 * Test: CacheExtension.
 */

use Nette\DI;
use Nette\Bridges\CacheDI\CacheExtension;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	$compiler = new DI\Compiler;
	$compiler->addExtension('cache', new CacheExtension(TEMP_DIR));

	eval($compiler->compile());

	$container = new Container;
	$container->initialize();

	$journal = $container->getService('cache.journal');
	Assert::type(Nette\Caching\Storages\SQLiteJournal::class, $journal);

	$storage = $container->getService('cache.storage');
	Assert::type(Nette\Caching\Storages\FileStorage::class, $storage);

	// aliases
	Assert::same($journal, $container->getService('nette.cacheJournal'));
	Assert::same($storage, $container->getService('cacheStorage'));
});
