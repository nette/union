<?php

/**
 * Test: Nette\Http\Session storage.
 */

use Nette\Http\Session,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Default session handler is not available in CLI');
}


class MySessionStorageExtension extends \SessionHandler
{
}


$factory = new Nette\Http\RequestFactory;
$session = new Nette\Http\Session($factory->createHttpRequest(), new Nette\Http\Response);

$session->setOptions(['save_handler' => 'files']);
$session->setHandler(new MySessionStorageExtension);
$session->start(); //and configure();
Assert::same('user', ini_get('session.save_handler'));
