<?php

/**
 * Test: {foreach} + {continueIf}, {breakIf}
 */

declare(strict_types=1);

use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$latte = new Latte\Engine;
$latte->setLoader(new Latte\Loaders\StringLoader);

$template = <<<'EOD'

{foreach [0, 1, 2, 3] as $item}
	{continueIf $item % 2}
	{$iterator->counter}. item
{/foreach}

---

{foreach [0, 1, 2, 3] as $item}
	{breakIf $item % 2}
	{$iterator->counter}. item
{/foreach}


EOD;

Assert::matchFile(
	__DIR__ . '/expected/CoreMacros.foreach.breaking.phtml',
	$latte->compile($template)
);

Assert::match(
	'
	1. item
	3. item

---

	1. item
',
	$latte->renderToString($template)
);


$template = <<<'EOD'

<ul title="foreach break">
	<li n:foreach="[0, 1, 2, 3] as $i">{$i}{breakIf true}</li>
</ul>

<ul title="foreach continue">
	<li n:foreach="[0, 1, 2, 3] as $i">{$i}{continueIf true}</li>
</ul>


<ul title="inner foreach break">
	<li n:inner-foreach="[0, 1, 2, 3] as $i">{$i}{breakIf true}</li>
</ul>

<ul title="inner foreach continue">
	<li n:inner-foreach="[0, 1, 2, 3] as $i">{$i}{continueIf true}</li>
</ul>

EOD;

Assert::matchFile(
	__DIR__ . '/expected/CoreMacros.foreach.breaking.attr.phtml',
	$latte->compile($template)
);

Assert::match(
	'
<ul title="foreach break">
	<li>0</li>
</ul>

<ul title="foreach continue">
	<li>0</li>
	<li>1</li>
	<li>2</li>
	<li>3</li>
</ul>


<ul title="inner foreach break">
	<li>0</li>
</ul>

<ul title="inner foreach continue">
	<li>0123</li>
</ul>
',
	$latte->renderToString($template)
);
