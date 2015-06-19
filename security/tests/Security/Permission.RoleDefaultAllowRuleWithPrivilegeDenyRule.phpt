<?php

/**
 * Test: Nette\Security\Permission Ensures that for a particular Role, a deny rule on a specific privilege is honored before an allow
 * rule on the entire ACL.
 */

use Nette\Security\Permission;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$acl = new Permission;
$acl->addRole('guest');
$acl->addRole('staff', 'guest');
$acl->deny();
$acl->allow('staff');
$acl->deny('staff', NULL, ['privilege1', 'privilege2']);
Assert::false($acl->isAllowed('staff', NULL, 'privilege1'));
