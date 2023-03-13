<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$latte = new Latte\Engine;
$latte->setLoader(new Latte\Loaders\StringLoader);

Assert::match(
	<<<'EOD'
		Main: true
		Foo: false
		EOD,
	$latte->renderToString(<<<'EOD'
		{block main}{/block}
		Main: {=var_export(blockExists(main), true)}
		Foo: {=var_export(blockExists(foo), true)}
		EOD),
);
