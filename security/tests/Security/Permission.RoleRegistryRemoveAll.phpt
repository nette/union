<?php

/**
 * Test: Nette\Security\Permission Ensures that removal of all Roles works.
 */

declare(strict_types=1);

use Nette\Security\Permission;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$acl = new Permission;
$acl->addRole('guest');
$acl->removeAllRoles();
Assert::false($acl->hasRole('guest'));
