<?php

declare(strict_types=1);

use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$latte = new Latte\Engine;
$latte->setLoader(new Latte\Loaders\StringLoader);

$template = <<<'EOD'
<div n:ifcontent n:snippet="test">
		static
</div>


{snippet outer}
begin
<div n:snippet="inner-$id" n:block="block2">
		dynamic
</div>
end
{/snippet}
EOD;

	$latte->render($template);
