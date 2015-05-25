<?php

/**
 * Test: Nette\Security\Permission Ensures that multiple privileges work properly.
 */

use Nette\Security\Permission,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$acl = new Permission;
$acl->allow(NULL, NULL, ['p1', 'p2', 'p3']);
Assert::true( $acl->isAllowed(NULL, NULL, 'p1') );
Assert::true( $acl->isAllowed(NULL, NULL, 'p2') );
Assert::true( $acl->isAllowed(NULL, NULL, 'p3') );
Assert::false( $acl->isAllowed(NULL, NULL, 'p4') );
$acl->deny(NULL, NULL, 'p1');
Assert::false( $acl->isAllowed(NULL, NULL, 'p1') );
$acl->deny(NULL, NULL, ['p2', 'p3']);
Assert::false( $acl->isAllowed(NULL, NULL, 'p2') );
Assert::false( $acl->isAllowed(NULL, NULL, 'p3') );
