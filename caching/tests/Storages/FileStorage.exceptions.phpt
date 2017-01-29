<?php

/**
 * Test: Nette\Caching\Storages\FileStorage exception situations.
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Assert::exception(function () {
	new FileStorage(TEMP_DIR . '/missing');
}, Nette\DirectoryNotFoundException::class, "Directory '%a%' not found.");


Assert::exception(function () {
	$storage = new FileStorage(TEMP_DIR);
	$storage->write('a', 'b', [Cache::TAGS => 'c']);
}, Nette\InvalidStateException::class, 'CacheJournal has not been provided.');
