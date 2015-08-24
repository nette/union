<?php

/**
 * Test: Nette\Security\Permission Ensures that the same Role cannot be registered more than once to the registry.
 */

use Nette\Security\Permission;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$acl = new Permission;
Assert::exception(function () use ($acl) {
	$acl->addRole('guest');
	$acl->addRole('guest');
}, Nette\InvalidStateException::class, "Role 'guest' already exists in the list.");
