<?php

/**
 * Test: Nette\Security\SimpleAuthenticator
 */

use Nette\Security\SimpleAuthenticator;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$users = [
	'john' => 'password123!',
	'admin' => 'admin',
];

$authenticator = new SimpleAuthenticator($users);

$identity = $authenticator->authenticate(['john', 'password123!']);
Assert::type(Nette\Security\IIdentity::class, $identity);
Assert::equal('john', $identity->getId());

$identity = $authenticator->authenticate(['admin', 'admin']);
Assert::type(Nette\Security\IIdentity::class, $identity);
Assert::equal('admin', $identity->getId());

Assert::exception(function () use ($authenticator) {
	$authenticator->authenticate(['admin', 'wrong password']);
}, Nette\Security\AuthenticationException::class, 'Invalid password.');

Assert::exception(function () use ($authenticator) {
	$authenticator->authenticate(['nobody', 'password']);
}, Nette\Security\AuthenticationException::class, "User 'nobody' not found.");
