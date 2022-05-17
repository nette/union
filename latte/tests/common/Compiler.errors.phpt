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
	"Unexpected end, expecting '}' (at column 2)",
);

Assert::exception(
	fn() => $latte->compile("{* \n'abc}"),
	Latte\CompileException::class,
	"Unexpected end, expecting '*}' (on line 2 at column 6)",
);

Assert::exception(
	fn() => $latte->compile('Block{/block}'),
	Latte\CompileException::class,
	"Unexpected '{/block' (at column 6)",
);

Assert::exception(
	fn() => $latte->compile("{var \n'abc}"),
	Latte\CompileException::class,
	'Unterminated string (on line 2 at column 1)',
);

Assert::exception(
	fn() => $latte->compile('<a {if}n:href>'),
	Latte\CompileException::class,
	'Attribute n:href must not appear inside {tags} (at column 8)',
);

Assert::exception(
	fn() => $latte->compile('<span title={if true}a b{/if}></span>'),
	Latte\CompileException::class,
	"Unexpected ' b{/if', expecting {/if} (at column 23)",
);

Assert::exception(
	fn() => $latte->compile('<a n:href n:href>'),
	Latte\CompileException::class,
	'Found multiple attributes n:href (at column 11)',
);

Assert::match(
	'<div c=comment -->',
	$latte->renderToString('<div c=comment {="--"}>'),
);

Assert::exception(
	fn() => $latte->compile('<a n:class class>'),
	Latte\CompileException::class,
	'It is not possible to combine class with n:class (at column 4)',
);

Assert::exception(
	fn() => $latte->compile('<p title=""</p>'),
	'Latte\CompileException',
	"Unexpected '</p>' (at column 12)",
);

Assert::exception(
	fn() => $latte->compile('<p title=>'),
	'Latte\CompileException',
	"Unexpected '>' (at column 10)",
);

Assert::exception(
	fn() => $latte->compile('{time() /}'),
	Latte\CompileException::class,
	'Unexpected /} in tag {= time()/} (at column 1)',
);


// <script> & <style> must be closed
Assert::exception(
	fn() => $latte->compile('<STYLE>'),
	Latte\CompileException::class,
	'Unexpected end, expecting </STYLE> for element started on line 1 (at column 8)',
);

Assert::exception(
	fn() => $latte->compile('<script>'),
	Latte\CompileException::class,
	'Unexpected end, expecting </script> for element started on line 1 (at column 9)',
);

Assert::noError(
	fn() => $latte->compile('{contentType xml}<script>'),
);


// brackets balancing
Assert::exception(
	fn() => $latte->compile('{=)}'),
	Latte\CompileException::class,
	'Unexpected )',
);

Assert::exception(
	fn() => $latte->compile('{=[(])}'),
	Latte\CompileException::class,
	'Unexpected ]',
);

Assert::exception(
	fn() => $latte->compile('{=[}'),
	Latte\CompileException::class,
	'Missing ]',
);


// forbidden keywords
Assert::exception(
	fn() => $latte->compile('{php function test() }'),
	Latte\CompileException::class,
	"Forbidden keyword 'function' inside tag.",
);

Assert::exception(
	fn() => $latte->compile('{php function /*comment*/ test() }'),
	Latte\CompileException::class,
	"Forbidden keyword 'function' inside tag.",
);

Assert::exception(
	fn() => $latte->compile('{php function &test() }'),
	Latte\CompileException::class,
	"Forbidden keyword 'function' inside tag.",
);

Assert::exception(
	fn() => $latte->compile('{php class test }'),
	Latte\CompileException::class,
	"Forbidden keyword 'class' inside tag.",
);

Assert::exception(
	fn() => $latte->compile('{php interface test }'),
	Latte\CompileException::class,
	"Forbidden keyword 'interface' inside tag.",
);

Assert::exception(
	fn() => $latte->compile('{php return}'),
	Latte\CompileException::class,
	"Forbidden keyword 'return' inside tag.",
);

Assert::exception(
	fn() => $latte->compile('{php yield $x}'),
	Latte\CompileException::class,
	"Forbidden keyword 'yield' inside tag.",
);

Assert::exception(
	fn() => $latte->compile('{php die() }'),
	Latte\CompileException::class,
	"Forbidden keyword 'die' inside tag.",
);

Assert::exception(
	fn() => $latte->compile('{php include "file" }'),
	Latte\CompileException::class,
	"Forbidden keyword 'include' inside tag.",
);

Assert::exception(
	fn() => $latte->compile('{=`whoami`}'),
	Latte\CompileException::class,
	"Unexpected '`' (at column 3)",
);

Assert::exception(
	fn() => $latte->compile('{$ʟ_tmp}'),
	Latte\CompileException::class,
	'Forbidden variable $ʟ_tmp.',
);


// unclosed macros
Assert::exception(
	fn() => $latte->compile('{if 1}'),
	Latte\CompileException::class,
	'Unexpected end, expecting {/if} (at column 7)',
);

Assert::exception(
	fn() => $latte->compile('<p n:foreach=1><span n:if=1>'),
	Latte\CompileException::class,
	'Unexpected end, expecting </span> for element started on line 1 (at column 29)',
);

Assert::exception(
	fn() => $latte->compile('<p n:foreach=1><span n:if=1></i>'),
	Latte\CompileException::class,
	"Unexpected '</i>', expecting </span> for element started on line 1 (at column 29)",
);

Assert::exception(
	fn() => $latte->compile('{/if}'),
	Latte\CompileException::class,
	"Unexpected '{/if}' (at column 1)",
);

Assert::exception(
	fn() => $latte->compile('{if 1}{/foreach}'),
	Latte\CompileException::class,
	'Unexpected {/foreach}, expecting {/if} (at column 7)',
);

Assert::exception(
	fn() => $latte->compile('{if 1}{/if 2}'),
	Latte\CompileException::class,
	'Unexpected {/if 2}, expecting {/if} (at column 7)',
);

Assert::exception(
	fn() => $latte->compile('<span n:if=1 n:foreach=2>{foreach x}</span>'),
	Latte\CompileException::class,
	'Unexpected end, expecting {/foreach} (at column 44)',
);

Assert::exception(
	fn() => $latte->compile('<span n:if=1 n:foreach=2>{/foreach}'),
	Latte\CompileException::class,
	"Unexpected '{/foreach', expecting </span> for element started on line 1 (at column 26)",
);

Assert::exception(
	fn() => $latte->compile('<span n:if=1 n:foreach=2>{/if}'),
	Latte\CompileException::class,
	"Unexpected '{/if}', expecting </span> for element started on line 1 (at column 26)",
);

Assert::exception(
	fn() => $latte->compile(<<<'XX'
				{foreach [] as $item}
					<li><a n:tag-if="$iterator->odd"></li>
				{/foreach}
		XX),
	Latte\CompileException::class,
	"Unexpected '</li>', expecting </a> for element started on line 2 (on line 2 at column 37)",
);

Assert::error(
	fn() => $latte->compile('{=foo|noescape|trim}'),
	E_USER_DEPRECATED,
	"Filter |noescape should be placed at the very end in '|noescape|trim'",
);
