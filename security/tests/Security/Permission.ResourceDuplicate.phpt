<?php

/**
 * Test: Nette\Security\Permission Ensures that the same Resource cannot be added more than once.
 */

use Nette\Security\Permission;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Assert::exception(function () {
	$acl = new Permission;
	$acl->addResource('area');
	$acl->addResource('area');
}, Nette\InvalidStateException::class, "Resource 'area' already exists in the list.");
