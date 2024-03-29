<?php

/**
 * Test: Tracy\Debugger notices and warnings in production mode.
 * @outputMatch
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = true;

Debugger::enable();

$x = &pi(); // E_NOTICE
hex2bin('a'); // E_WARNING
require __DIR__ . '/fixtures/E_COMPILE_WARNING.php'; // E_COMPILE_WARNING
// E_COMPILE_WARNING is handled in shutdownHandler()
