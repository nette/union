<?php

/** @phpVersion < 8 */

declare(strict_types=1);

use Latte\Compiler\MacroTokens;
use Latte\Compiler\PhpWriter;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


function optionalChaining($code)
{
	$writer = new PhpWriter(new MacroTokens);
	return $writer->optionalChainingPass(new MacroTokens($code))->joinUntil();
}


test('properties', function () {
	Assert::same('(($__tmp = $foo) === null ? null : $__tmp->prop)', optionalChaining('$foo?->prop'));
	Assert::same('(($__tmp = $foo) === null ? null : $__tmp->prop) + 10', optionalChaining('$foo?->prop + 10'));
	Assert::same('((($__tmp = $foo) === null ? null : $__tmp->prop))', optionalChaining('($foo?->prop)'));
	Assert::same('[(($__tmp = $foo) === null ? null : $__tmp->prop)]', optionalChaining('[$foo?->prop]'));

	// variable
	Assert::same('(($__tmp = $foo) === null ? null : $__tmp->$prop)', optionalChaining('$foo?->$prop'));
});


test('calling', function () {
	Assert::same('(($__tmp = $foo) === null ? null : $__tmp->call())', optionalChaining('$foo?->call()'));
	Assert::same('(($__tmp = $foo) === null ? null : $__tmp->call()) + 10', optionalChaining('$foo?->call() + 10'));
	Assert::same('((($__tmp = $foo) === null ? null : $__tmp->call()))', optionalChaining('($foo?->call())'));
	Assert::same('(($__tmp = $foo) === null ? null : (($__tmp = $__tmp->call( (($__tmp = $a) === null ? null : $__tmp->call()) )) === null ? null : $__tmp->x))', optionalChaining('$foo?->call( $a?->call() )?->x'));
});


test('mixed', function () {
	Assert::same('$var->prop->elem[1]->call(2)->item', optionalChaining('$var->prop->elem[1]->call(2)->item'));
	Assert::same('(($__tmp = $var) === null ? null : $__tmp->prop->elem[1]->call(2)->item)', optionalChaining('$var?->prop->elem[1]->call(2)->item'));
	Assert::same('(($__tmp = $var->prop) === null ? null : $__tmp->elem[1]->call(2)->item)', optionalChaining('$var->prop?->elem[1]->call(2)->item'));
	Assert::same('(($__tmp = $var->prop->elem[1]) === null ? null : $__tmp->call(2)->item)', optionalChaining('$var->prop->elem[1]?->call(2)->item'));
	Assert::same('(($__tmp = $var->prop->elem[1]->call(2)) === null ? null : $__tmp->item)', optionalChaining('$var->prop->elem[1]->call(2)?->item'));
});
