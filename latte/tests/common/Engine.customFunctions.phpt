<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Latte\Engine;
$latte->setLoader(new Latte\Loaders\StringLoader);

// global function
$latte->addFunction('fnc', 'trim');
Assert::same('aa', $latte->invokeFunction('fnc', ['  aa  ']));
Assert::same(
	'abc',
	$latte->renderToString('{fnc(" abc ")}'),
);


// static method
$latte->addFunction('fnc', 'Latte\Essential\Filters::reverse');
Assert::same(
	' cba ',
	$latte->renderToString('{fnc(" abc ")}'),
);


// static method 2
$latte->addFunction('fnc', [Latte\Essential\Filters::class, 'reverse']);
Assert::same(
	' cba ',
	$latte->renderToString('{fnc(" abc ")}'),
);


// object method
class Test
{
	public function m($val)
	{
		return $val * 2;
	}
}

$latte->addFunction('fnc', [new Test, 'm']);
Assert::same(
	'246',
	$latte->renderToString('{fnc(123)}'),
);


// closure
$latte->addFunction('fnc', fn($val) => $val * 2);
Assert::same(
	'246',
	$latte->renderToString('{fnc(123)}'),
);


// case insensitive
$latte->addFunction('fNC', 'trim');
Assert::same(
	'abc',
	$latte->renderToString('{fNC(" abc ")}'),
);

Assert::error(
	fn() => $latte->compile('{Fnc(123)}'),
	E_USER_WARNING,
	"Case mismatch on function name 'Fnc', correct name is 'fNC'.",
);


// invoke function
Assert::exception(
	fn() => $latte->invokeFunction('unknown', []),
	LogicException::class,
	"Function 'unknown' is not defined.",
);
