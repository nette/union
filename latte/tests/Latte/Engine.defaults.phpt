<?php

declare(strict_types=1);

use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$latte = new Latte\Engine;
Assert::same(
	'12345',
	$latte->invokeFilter('reverse', ['54321'])
);
