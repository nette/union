<?php

/**
 * Test: Nette\Security\Permission Ensures that for a particular Role, a deny rule on a specific privilege is honored before an allow
 * rule on the entire ACL.
 */

declare(strict_types=1);

use Nette\Security\Permission;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$acl = new Permission;
$acl->addRole('guest');
$acl->addRole('staff', 'guest');
$acl->deny();
$acl->allow('staff');
$acl->deny('staff', null, ['privilege1', 'privilege2']);
Assert::false($acl->isAllowed('staff', null, 'privilege1'));
