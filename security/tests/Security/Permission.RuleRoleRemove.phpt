<?php

/**
 * Test: Nette\Security\Permission Ensures that removal of a Role results in its rules being removed.
 */

use Nette\Security\Permission;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$acl = new Permission;
$acl->addRole('guest');
$acl->allow('guest');
Assert::true($acl->isAllowed('guest'));
$acl->removeRole('guest');
Assert::exception(function () use ($acl) {
	$acl->isAllowed('guest');
}, Nette\InvalidStateException::class, "Role 'guest' does not exist.");

$acl->addRole('guest');
Assert::false($acl->isAllowed('guest'));
