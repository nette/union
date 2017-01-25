<?php

/**
 * Test: Nette\Security\Permission Ensures that multiple privileges work properly for a particular Role.
 */

declare(strict_types=1);

use Nette\Security\Permission;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$acl = new Permission;
$acl->addRole('guest');
$acl->allow('guest', NULL, ['p1', 'p2', 'p3']);
Assert::true($acl->isAllowed('guest', NULL, 'p1'));
Assert::true($acl->isAllowed('guest', NULL, 'p2'));
Assert::true($acl->isAllowed('guest', NULL, 'p3'));
Assert::false($acl->isAllowed('guest', NULL, 'p4'));
$acl->deny('guest', NULL, 'p1');
Assert::false($acl->isAllowed('guest', NULL, 'p1'));
$acl->deny('guest', NULL, ['p2', 'p3']);
Assert::false($acl->isAllowed('guest', NULL, 'p2'));
Assert::false($acl->isAllowed('guest', NULL, 'p3'));
