<?php

/**
 * Test: Tracy\Debugger logging E_NOTICE (bluescreen) in development mode.
 */

use Tracy\Debugger,
  Tester\Assert;


require __DIR__ . '/../bootstrap.php';


// Setup environment
Debugger::enable(Debugger::DEVELOPMENT, TEMP_DIR);
Debugger::$logSeverity = E_NOTICE;

$variable = $missingVariable;

Assert::same(0, count(glob(TEMP_DIR . '/exception*.html')));
Assert::same(0, count(glob(TEMP_DIR . '/error.log')));
