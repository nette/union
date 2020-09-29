<?php

/**
 * Test: Latte\PhpWriter::formatModifiers()
 */

declare(strict_types=1);

use Latte\MacroTokens;
use Latte\PhpWriter;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


function formatModifiers($arg, $modifiers, $isContent = false)
{
	$writer = new PhpWriter(new MacroTokens(''), $modifiers, ['html', 'x']);
	return $writer->formatModifiers($arg, $isContent);
}


test('special', function () {
	Assert::same('@', formatModifiers('@', ''));
	Assert::same('@', formatModifiers('@', '|'));
	Assert::exception(function () {
		formatModifiers('@', ':');
	}, Latte\CompileException::class, 'Modifier name must be alphanumeric string%a%');
	Assert::exception(function () {
		Assert::same('($this->filters->mod)(@, \'\\\\\', "a", "b", "c", "arg2")', formatModifiers('@', "mod:'\\\\':a:b:c':arg2"));
	}, Latte\CompileException::class, 'Unexpected %a% on line 1, column 15.');
});


test('common', function () {
	Assert::same('($this->filters->mod)(@)', formatModifiers('@', 'mod'));
	Assert::same('($this->filters->mod3)(($this->filters->mod2)(($this->filters->mod1)(@)))', formatModifiers('@', 'mod1|mod2|mod3'));
});


test('arguments', function () {
	Assert::same('($this->filters->mod)(@, \'arg1\', 2, $var["pocet"])', formatModifiers('@', 'mod:arg1:2:$var["pocet"]'));
	Assert::same('($this->filters->mod)(@, \'arg1\', 2, $var["pocet"])', formatModifiers('@', 'mod,arg1,2,$var["pocet"]'));
	Assert::same('($this->filters->mod)(@, " :a:b:c", "", 3, "")', formatModifiers('@', 'mod:" :a:b:c":"":3:""'));
	Assert::same('($this->filters->mod)(@, "\":a:b:c")', formatModifiers('@', 'mod:"\\":a:b:c"'));
	Assert::same("(\$this->filters->mod)(@, '\':a:b:c')", formatModifiers('@', "mod:'\\':a:b:c'"));
	Assert::same('($this->filters->mod)(@ , \'param\' , \'param\')', formatModifiers('@', 'mod : param : param'));
	Assert::same('($this->filters->mod)(@, $var, 0, -0.0, "str", \'str\')', formatModifiers('@', 'mod, $var, 0, -0.0, "str", \'str\''));
	Assert::same('($this->filters->mod)(@, true, false, null)', formatModifiers('@', 'mod: true, false, null'));
	Assert::same('($this->filters->mod)(@, TRUE, FALSE, NULL)', formatModifiers('@', 'mod: TRUE, FALSE, NULL'));
	Assert::same('($this->filters->mod)(@, \'True\', \'False\', \'Null\')', formatModifiers('@', 'mod: True, False, Null'));
	Assert::same('($this->filters->mod)(@, array(1))', formatModifiers('@', 'mod: array(1)'));
});

test('inline modifiers', function () {
	Assert::same('($this->filters->mod)(@, ($this->filters->mod2)(2))', formatModifiers('@', 'mod:(2|mod2)'));
});

test('FilterInfo aware modifiers', function () {
	Assert::same('$this->filters->filterContent(\'mod\', $_fi, @)', formatModifiers('@', 'mod', true));
	Assert::same('LR\Filters::convertTo($_fi, \'htmlx\', $this->filters->filterContent(\'mod2\', $_fi, $this->filters->filterContent(\'mod1\', $_fi, @)))', formatModifiers('@', 'mod1|mod2|escape', true));
});

test('depth', function () {
	Assert::same('($this->filters->mod)(@, (1?2:3))', formatModifiers('@', 'mod:(1?2:3)'));
});


test('optionalChainingPass', function () {
	Assert::same('($this->filters->mod)(@, ($a ?? null))', formatModifiers('@', 'mod:$a?'));
	Assert::same('($this->filters->mod)(@, (($a ?? null)))', formatModifiers('@', 'mod:($a?)'));
	Assert::same('($this->filters->mod)(@, (($_tmp = $var ?? null) === null ? null : (($_tmp = $_tmp->prop ?? null) === null ? null : (($_tmp = $_tmp->elem[1] ?? null) === null ? null : (($_tmp = $_tmp->call(2) ?? null) === null ? null : ($_tmp->item ?? null))))))', formatModifiers('@', 'mod:$var?->prop?->elem[1]?->call(2)?->item?'));
});


test('optionalChainingPass + ternary', function () {
	Assert::same('($this->filters->mod)(@, $a ?, $b)', formatModifiers('@', 'mod:$a?:$b'));
	Assert::same('($this->filters->mod)(@, $a ? , $b)', formatModifiers('@', 'mod:$a ? : $b'));
	Assert::same('($this->filters->mod)(@, $a ?? $b)', formatModifiers('@', 'mod:$a ?? $b'));
	Assert::same('($this->filters->mod)(@, (($_tmp = $a ?? null) === null ? null : ($_tmp->foo ?? null)) ? [1, 2, ([3 ? 2 : 1])] , $b)', formatModifiers('@', 'mod:$a?->foo? ? [1, 2, ([3 ? 2 : 1])] : $b'));
});
