<?php

/**
 * Test: Nette\Http\Session setOptions error.
 */

declare(strict_types=1);

use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$factory = new Nette\Http\RequestFactory;
$session = new Nette\Http\Session($factory->createHttpRequest(), new Nette\Http\Response);
$session->start();

Assert::exception(function () use ($session) {
	$session->setOptions([
		'gc_malifetime' => 123,
	]);
}, Nette\InvalidStateException::class, "Invalid session configuration option 'gc_malifetime' or 'gcMalifetime', did you mean 'gc_maxlifetime' or 'gcMaxlifetime'?");

Assert::exception(function () use ($session) {
	$session->setOptions([
		'cookieDoman' => '.domain.com',
	]);
}, Nette\InvalidStateException::class, "Invalid session configuration option 'cookie_doman' or 'cookieDoman', did you mean 'cookie_domain' or 'cookieDomain'?");
