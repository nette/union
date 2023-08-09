<?php

// Array dereferencing

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$test = <<<'XX'
	[1, 2, 3][2],
	[1, 2, 3][2][0][0],
	XX;

$node = parseCode($test);
$code = printNode($node);

Assert::same(
	loadContent(__FILE__, __COMPILER_HALT_OFFSET__),
	$code,
);

__halt_compiler();
[1, 2, 3][2],
[1, 2, 3][2][0][0]
