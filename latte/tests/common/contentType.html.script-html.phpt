<?php

/**
 * Test: HTML in <script>
 */

declare(strict_types=1);

use Latte\Runtime\Html;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Latte\Engine;
$latte->setLoader(new Latte\Loaders\StringLoader);

// escaping of string
Assert::match(
	'<script type="text/html"> <div title=\'&apos; &lt;/script&gt;\'>  &lt;/script&gt; </div> </script>',
	$latte->renderToString('<script type="text/html"> <div title=\'{="\' </script>"}\'>  {="</script>"} </div> </script>'),
);

// escaping of Html object
Assert::match(
	'<script type="text/html"> <div title=\'</x-script>\'></div> </script>',
	$latte->renderToString(
		'<script type="text/html"> {$foo} </script>',
		['foo' => new Html("<div title='</script>'></div>")],
	),
);

// no escape
Assert::match(
	'<script type="text/html"></x-script></script>',
	$latte->renderToString('<script type="text/html">{="</script>"|noescape}</script>'),
);

// include
Assert::match(
	' <script type="text/html"><x-script></x-script></script>',
	$latte->renderToString('{define a}<script></script>{/define} <script type="text/html">{include a}</script>'),
);

// content of <script> is RAWTEXT
Assert::match(
	<<<'XX'
			<script type="text/html">
			<div n:foreach="[a, b] as $i">def</div>
			</script>
			<div>a</div>
			<div>b</div>

		XX,
	$latte->renderToString(
		<<<'XX'

				{var $i = def}
				<script type="text/html">
				<div n:foreach="[a, b] as $i">{$i}</div>
				</script>
				<div n:foreach="[a, b] as $i">{$i}</div>

			XX,
	),
);

// content of <script> changed to html
Assert::match(
	<<<'XX'
			<script type="text/html">
			<div>a</div>
			<div>b</div>
			</script>
			<div>a</div>
			<div>b</div>

		XX,
	$latte->renderToString(
		<<<'XX'

				{var $i = def}
				<script type="text/html">
				{contentType html}
				<div n:foreach="[a, b] as $i">{$i}</div>
				</script>
				<div n:foreach="[a, b] as $i">{$i}</div>

			XX,
	),
);
