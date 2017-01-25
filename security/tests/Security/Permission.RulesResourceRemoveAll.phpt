<?php

/**
 * Test: Nette\Security\Permission Ensures that removal of all Resources results in Resource-specific rules being removed.
 */

declare(strict_types=1);

use Nette\Security\Permission;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$acl = new Permission;
$acl->addResource('area');
$acl->allow(NULL, 'area');
Assert::true($acl->isAllowed(NULL, 'area'));
$acl->removeAllResources();
Assert::exception(function () use ($acl) {
	$acl->isAllowed(NULL, 'area');
}, Nette\InvalidStateException::class, "Resource 'area' does not exist.");

$acl->addResource('area');
Assert::false($acl->isAllowed(NULL, 'area'));
