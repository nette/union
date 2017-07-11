<?php

/**
 * Test: Tracy\Debugger errors and shut-up operator.
 * @exitCode   255
 * @httpCode   500
 * @outputMatch Error%a?%: Call to undefined function missing_function() in %A%
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
header('Content-Type: text/plain');

Debugger::enable();

@missing_function();
