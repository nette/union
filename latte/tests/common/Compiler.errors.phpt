<?php

/**
 * Test: Compile errors.
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Latte\Engine;
$latte->setLoader(new Latte\Loaders\StringLoader);

Assert::exception(
	fn() => $latte->compile('{'),
	Latte\CompileException::class,
	'Unterminated Latte tag (on line 1 at column 2)',
);

Assert::exception(
	fn() => $latte->compile("{* \n'abc}"),
	Latte\CompileException::class,
	'Unterminated Latte comment (on line 1 at column 3)',
);

Assert::exception(
	fn() => $latte->compile('Block{/block}'),
	Latte\CompileException::class,
	"Unexpected '{/block' (on line 1 at column 6)",
);

Assert::exception(
	fn() => $latte->compile("{var \n'abc}"),
	Latte\CompileException::class,
	'Unterminated string (on line 2 at column 1)',
);

Assert::exception(
	fn() => $latte->compile('<a {if}n:href>'),
	Latte\CompileException::class,
	'Attribute n:href must not appear inside {tags} (on line 1 at column 8)',
);

Assert::exception(
	fn() => $latte->compile('<span title={if true}a b{/if}></span>'),
	Latte\CompileException::class,
	"Unexpected ' b{/if', expecting {/if} (on line 1 at column 23)",
);

Assert::exception(
	fn() => $latte->compile('<span title={if true}{$a}{$b}{/if}></span>'),
	Latte\CompileException::class,
	'Unexpected tag {=$b} (on line 1 at column 26)',
);

Assert::exception(
	fn() => $latte->compile('<a n:href n:href>'),
	Latte\CompileException::class,
	'Found multiple attributes n:href (on line 1 at column 11)',
);

Assert::match(
	'<div c=comment -->',
	$latte->renderToString('<div c=comment {="--"}>'),
);

Assert::exception(
	fn() => $latte->compile('<a n:class class>'),
	Latte\CompileException::class,
	'It is not possible to combine class with n:class (on line 1 at column 4)',
);

Assert::exception(
	fn() => $latte->compile('<p title=""</p>'),
	'Latte\CompileException',
	"Unexpected '</p>' (on line 1 at column 12)",
);

Assert::exception(
	fn() => $latte->compile('<p title=>'),
	'Latte\CompileException',
	"Unexpected '>' (on line 1 at column 10)",
);

Assert::exception(
	fn() => $latte->compile('<a {$foo}<'),
	Latte\CompileException::class,
	"Unexpected '<' (on line 1 at column 10)",
);

Assert::exception(
	fn() => $latte->compile('{time() /}'),
	Latte\CompileException::class,
	'Unexpected /} in tag {=time() /} (on line 1 at column 1)',
);


// <script> & <style> must be closed
Assert::exception(
	fn() => $latte->compile('<STYLE>'),
	Latte\CompileException::class,
	'Unexpected end, expecting </STYLE> for element started on line 1 at column 1 (on line 1 at column 8)',
);

Assert::exception(
	fn() => $latte->compile('<script>'),
	Latte\CompileException::class,
	'Unexpected end, expecting </script> for element started on line 1 at column 1 (on line 1 at column 9)',
);

Assert::noError(
	fn() => $latte->compile('{contentType xml}<script>'),
);


// brackets balancing
Assert::exception(
	fn() => $latte->compile('{=)}'),
	Latte\CompileException::class,
	"Unexpected ')' (on line 1 at column 3)",
);

Assert::exception(
	fn() => $latte->compile('{=[(])}'),
	Latte\CompileException::class,
	"Unexpected ']' (on line 1 at column 5)",
);


// forbidden keywords
Assert::exception(
	fn() => $latte->compile('{= function test() }'),
	Latte\CompileException::class,
	"Unexpected 'test' (on line 1 at column 13)",
);

Assert::exception(
	fn() => $latte->compile('{= class test }'),
	Latte\CompileException::class,
	"Unexpected 'test' (on line 1 at column 10)",
);

Assert::exception(
	fn() => $latte->compile('{= return}'),
	Latte\CompileException::class,
	"Unexpected 'return' (on line 1 at column 4)",
);

Assert::noError( // prints 'yield'
	fn() => $latte->compile('{= yield}'),
);

Assert::exception(
	fn() => $latte->compile('{= yield $x}'),
	Latte\CompileException::class,
	"Unexpected '\$x' (on line 1 at column 10)",
);

Assert::exception(
	fn() => $latte->compile('{=`whoami`}'),
	Latte\CompileException::class,
	"Unexpected '`' (on line 1 at column 3)",
);

Assert::exception(
	fn() => $latte->compile('{$ʟ_tmp}'),
	Latte\CompileException::class,
	'Forbidden variable $ʟ_tmp (on line 1 at column 2)',
);


// unclosed macros
Assert::exception(
	fn() => $latte->compile('{if 1}'),
	Latte\CompileException::class,
	'Unexpected end, expecting {/if} (on line 1 at column 7)',
);

Assert::exception(
	fn() => $latte->compile('<p n:if=1><span n:if=1>'),
	Latte\CompileException::class,
	'Unexpected end, expecting </span> for element started on line 1 at column 11 (on line 1 at column 24)',
);

Assert::exception(
	fn() => $latte->compile('<p n:if=1><span n:if=1></i>'),
	Latte\CompileException::class,
	"Unexpected '</i>', expecting </span> for element started on line 1 at column 11 (on line 1 at column 24)",
);

Assert::exception(
	fn() => $latte->compile('{/if}'),
	Latte\CompileException::class,
	"Unexpected '{/if}' (on line 1 at column 1)",
);

Assert::exception(
	fn() => $latte->compile('{if 1}{/foreach}'),
	Latte\CompileException::class,
	'Unexpected {/foreach}, expecting {/if} (on line 1 at column 7)',
);

Assert::exception(
	fn() => $latte->compile('{if 1}{/if 2}'),
	Latte\CompileException::class,
	"Unexpected '2', expecting end of tag in {/if} (on line 1 at column 12)",
);

Assert::exception(
	fn() => $latte->compile('<span n:if=1>{foreach $a as $b}</span>'),
	Latte\CompileException::class,
	'Unexpected end, expecting {/foreach} (on line 1 at column 39)',
);

Assert::exception(
	fn() => $latte->compile('<span n:if=1>{/if}'),
	Latte\CompileException::class,
	"Unexpected '{/if}', expecting </span> for element started on line 1 at column 1 (on line 1 at column 14)",
);

Assert::exception(
	fn() => $latte->compile(<<<'XX'
				{foreach [] as $item}
					<li><a n:tag-if="$iterator->odd"></li>
				{/foreach}
		XX),
	Latte\CompileException::class,
	"Unexpected '</li>', expecting </a> for element started on line 2 at column 8 (on line 2 at column 37)",
);
