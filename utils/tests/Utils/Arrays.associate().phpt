<?php

/**
 * Test: Nette\Utils\Arrays::associate()
 */

use Nette\Utils\Arrays,
	Nette\Utils\DateTime,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$arr = [
	['name' => 'John', 'age' => 11],
	['name' => 'John', 'age' => 22],
	['name' => 'Mary', 'age' => NULL],
	['name' => 'Paul', 'age' => 44],
];


Assert::same(
	[
		'John' => ['name' => 'John', 'age' => 11],
		'Mary' => ['name' => 'Mary', 'age' => NULL],
		'Paul' => ['name' => 'Paul', 'age' => 44],
	],
	Arrays::associate($arr, 'name')
);

Assert::same(
	[],
	Arrays::associate([], 'name')
);

Assert::same(
	[
		'John' => ['name' => 'John', 'age' => 11],
		'Mary' => ['name' => 'Mary', 'age' => NULL],
		'Paul' => ['name' => 'Paul', 'age' => 44],
	],
	Arrays::associate($arr, 'name=')
);

Assert::same(
	['John' => 22, 'Mary' => NULL, 'Paul' => 44],
	Arrays::associate($arr, 'name=age')
);

Assert::same( // path as array
	['John' => 22, 'Mary' => NULL, 'Paul' => 44],
	Arrays::associate($arr, ['name', '=', 'age'])
);

Assert::equal(
	[
		'John' => (object) [
			'name' => 'John',
			'age' => 11,
		],
		'Mary' => (object) [
			'name' => 'Mary',
			'age' => NULL,
		],
		'Paul' => (object) [
			'name' => 'Paul',
			'age' => 44,
		],
	],
	Arrays::associate($arr, 'name->')
);

Assert::equal(
	[
		11 => (object) [
			'John' => ['name' => 'John', 'age' => 11],
		],
		22 => (object) [
			'John' => ['name' => 'John', 'age' => 22],
		],
		NULL => (object) [
			'Mary' => ['name' => 'Mary', 'age' => NULL],
		],
		44 => (object) [
			'Paul' => ['name' => 'Paul', 'age' => 44],
		],
	],
	Arrays::associate($arr, 'age->name')
);

Assert::equal(
	(object) [
		'John' => ['name' => 'John', 'age' => 11],
		'Mary' => ['name' => 'Mary', 'age' => NULL],
		'Paul' => ['name' => 'Paul', 'age' => 44],
	],
	Arrays::associate($arr, '->name')
);

Assert::equal(
	(object) [],
	Arrays::associate([], '->name')
);

Assert::same(
	[
		'John' => [
			11 => ['name' => 'John', 'age' => 11],
			22 => ['name' => 'John', 'age' => 22],
		],
		'Mary' => [
			NULL => ['name' => 'Mary', 'age' => NULL],
		],
		'Paul' => [
			44 => ['name' => 'Paul', 'age' => 44],
		],
	],
	Arrays::associate($arr, 'name|age')
);

Assert::same(
	[
		'John' => ['name' => 'John', 'age' => 11],
		'Mary' => ['name' => 'Mary', 'age' => NULL],
		'Paul' => ['name' => 'Paul', 'age' => 44],
	],
	Arrays::associate($arr, 'name|')
);

Assert::same(
	[
		'John' => [
			['name' => 'John', 'age' => 11],
			['name' => 'John', 'age' => 22],
		],
		'Mary' => [
			['name' => 'Mary', 'age' => NULL],
		],
		'Paul' => [
			['name' => 'Paul', 'age' => 44],
		],
	],
	Arrays::associate($arr, 'name[]')
);

Assert::same(
	[
		['John' => ['name' => 'John', 'age' => 11]],
		['John' => ['name' => 'John', 'age' => 22]],
		['Mary' => ['name' => 'Mary', 'age' => NULL]],
		['Paul' => ['name' => 'Paul', 'age' => 44]],
	],
	Arrays::associate($arr, '[]name')
);

Assert::same(
	['John', 'John', 'Mary', 'Paul'],
	Arrays::associate($arr, '[]=name')
);

Assert::same(
	[
		'John' => [
			[11 => ['name' => 'John', 'age' => 11]],
			[22 => ['name' => 'John', 'age' => 22]],
		],
		'Mary' => [
			[NULL => ['name' => 'Mary', 'age' => NULL]],
		],
		'Paul' => [
			[44 => ['name' => 'Paul', 'age' => 44]],
		],
	],
	Arrays::associate($arr, 'name[]age')
);

Assert::same(
	$arr,
	Arrays::associate($arr, '[]')
);

// converts object to array
Assert::same(
	$arr,
	Arrays::associate($arr = [
		(object) ['name' => 'John', 'age' => 11],
		(object) ['name' => 'John', 'age' => 22],
		(object) ['name' => 'Mary', 'age' => NULL],
		(object) ['name' => 'Paul', 'age' => 44],
	], '[]')
);

// allowes objects in keys
Assert::equal(
	['2014-02-05 00:00:00' => new DateTime('2014-02-05')],
	Arrays::associate($arr = [
		['date' => new DateTime('2014-02-05')],
	], 'date=date')
);
Assert::equal(
	(object) ['2014-02-05 00:00:00' => new DateTime('2014-02-05')],
	Arrays::associate($arr = [
		['date' => new DateTime('2014-02-05')],
	], '->date=date')
);
