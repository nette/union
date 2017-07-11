<?php

/**
 * Test: Tracy\Dumper::toText() with location
 */

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


Assert::match('"Hello" (5)
in %a%:%d%
', Dumper::toText(trim(' Hello '), ['location' => TRUE]));
