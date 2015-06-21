<?php

/**
 * Test: Nette\Caching\Storages\FileStorage exception situations.
 */

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Assert::exception(function () {
	new FileStorage(TEMP_DIR . '/missing');
}, 'Nette\DirectoryNotFoundException', "Directory '%a%' not found.");


Assert::exception(function () {
	$storage = new FileStorage(TEMP_DIR);
	$storage->write('a', 'b', [Cache::TAGS => 'c']);
}, 'Nette\InvalidStateException', 'CacheJournal has not been provided.');
