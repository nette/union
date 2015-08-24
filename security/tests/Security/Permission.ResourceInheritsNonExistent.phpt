<?php

/**
 * Test: Nette\Security\Permission Ensures that an exception is thrown when a non-existent Resource is specified to each parameter of inherits().
 */

use Nette\Security\Permission;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$acl = new Permission;
$acl->addResource('area');
Assert::exception(function () use ($acl) {
	$acl->resourceInheritsFrom('nonexistent', 'area');
}, Nette\InvalidStateException::class, "Resource 'nonexistent' does not exist.");

Assert::exception(function () use ($acl) {
	$acl->resourceInheritsFrom('area', 'nonexistent');
}, Nette\InvalidStateException::class, "Resource 'nonexistent' does not exist.");
