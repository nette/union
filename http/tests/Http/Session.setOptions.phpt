<?php

/**
 * Test: Nette\Http\Session setOptions.
 */

declare(strict_types=1);

use Nette\Http\Session;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$factory = new Nette\Http\RequestFactory;
$session = new Nette\Http\Session($factory->createHttpRequest(), new Nette\Http\Response);

Assert::same([
	'referer_check' => '',
	'use_cookies' => 1,
	'use_only_cookies' => 1,
	'use_trans_sid' => 0,
	'cookie_lifetime' => 0,
	'cookie_path' => '/',
	'cookie_domain' => '',
	'cookie_secure' => FALSE,
	'cookie_httponly' => TRUE,
	'gc_maxlifetime' => 10800,
], $session->getOptions());

$session->setOptions([
	'cookieDomain' => '.domain.com',
]);
Assert::same([
	'cookie_domain' => '.domain.com',
	'referer_check' => '',
	'use_cookies' => 1,
	'use_only_cookies' => 1,
	'use_trans_sid' => 0,
	'cookie_lifetime' => 0,
	'cookie_path' => '/',
	'cookie_secure' => FALSE,
	'cookie_httponly' => TRUE,
	'gc_maxlifetime' => 10800,
], $session->getOptions());

$session->setOptions([
	'session.cookie_domain' => '.domain.org',
]);
Assert::same([
	'cookie_domain' => '.domain.org',
	'referer_check' => '',
	'use_cookies' => 1,
	'use_only_cookies' => 1,
	'use_trans_sid' => 0,
	'cookie_lifetime' => 0,
	'cookie_path' => '/',
	'cookie_secure' => FALSE,
	'cookie_httponly' => TRUE,
	'gc_maxlifetime' => 10800,
], $session->getOptions());
