<?php

/**
 * Test: Nette\Utils\Arrays::renameKey()
 */

declare(strict_types=1);

use Nette\Utils\Arrays;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$arr = [
	'' => 'first',
	0 => 'second',
	7 => 'fourth',
	1 => 'third',
];

Arrays::renameKey($arr, '1', 'new1');
Assert::same([
	'' => 'first',
	0 => 'second',
	7 => 'fourth',
	'new1' => 'third',
], $arr);

Arrays::renameKey($arr, 0, 'new2');
Assert::same([
	'' => 'first',
	'new2' => 'second',
	7 => 'fourth',
	'new1' => 'third',
], $arr);

Arrays::renameKey($arr, null, 'new3');
Assert::same([
	'new3' => 'first',
	'new2' => 'second',
	7 => 'fourth',
	'new1' => 'third',
], $arr);

Arrays::renameKey($arr, '', 'new4');
Assert::same([
	'new3' => 'first',
	'new2' => 'second',
	7 => 'fourth',
	'new1' => 'third',
], $arr);

Arrays::renameKey($arr, 'undefined', 'new5');
Assert::same([
	'new3' => 'first',
	'new2' => 'second',
	7 => 'fourth',
	'new1' => 'third',
], $arr);
