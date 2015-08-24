<?php

/**
 * Test: Nette\Http\Session error in session_start.
 */

use Nette\Http\Session;
use Nette\Http\SessionSection;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


ini_set('session.save_path', ';;;');


$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript), new Nette\Http\Response);

Assert::exception(function () use ($session) {
	$session->start();
}, Nette\InvalidStateException::class, '%a?%open(%A%) failed: %a%');
