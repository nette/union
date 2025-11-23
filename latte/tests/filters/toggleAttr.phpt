<?php

/**
 * Test: Latte\Essential\Filters::toggleAttr()
 */

declare(strict_types=1);

use Latte\Essential\Filters;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// truthy
Assert::null(Filters::toggleAttr(''));
Assert::null(Filters::toggleAttr(0));
Assert::null(Filters::toggleAttr(null));
Assert::null(Filters::toggleAttr(false));
Assert::null(Filters::toggleAttr([]));

// falsey
Assert::same('', Filters::toggleAttr('a'));
Assert::same('', Filters::toggleAttr(1));
Assert::same('', Filters::toggleAttr(true));
Assert::same('', Filters::toggleAttr([1]));
