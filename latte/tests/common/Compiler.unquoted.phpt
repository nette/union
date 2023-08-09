<?php

/**
 * Test: unquoted attributes.
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Latte\Engine;
$latte->setLoader(new Latte\Loaders\StringLoader);

$template = <<<'EOD'
	<span title={$x} class={$x}></span>

	<span title={$x} {$x}></span>

	<span title={if true}{$x}{else}item{/if}></span>

	<span {='title'}={$x}></span>

	<span attr=c{$x}d></span>

	<span onclick={$x} {$x}></span>

	<span onclick=c{$x}d></span>

	<span attr{$x}b=c{$x}d></span>

	EOD;

Assert::matchFile(
	__DIR__ . '/expected/Compiler.unquoted.attrs.php',
	$latte->compile($template),
);
Assert::matchFile(
	__DIR__ . '/expected/Compiler.unquoted.attrs.html',
	$latte->renderToString($template, ['x' => '\' & "']),
);
