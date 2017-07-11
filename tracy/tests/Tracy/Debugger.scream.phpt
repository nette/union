<?php

/**
 * Test: Tracy\Debugger notices and warnings in scream mode.
 * @outputMatchFile Debugger.scream.expect
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = FALSE;
Debugger::$scream = TRUE;
header('Content-Type: text/plain; charset=utf-8');

Debugger::enable();

@mktime(); // E_STRICT in PHP 5, E_DEPRECATED in PHP 7
PHP_MAJOR_VERSION < 7 ? @mktime(0, 0, 0, 1, 23, 1978, 1) : @mktime(); // E_DEPRECATED
@$x++; // E_NOTICE
@min(1); // E_WARNING
@require 'E_COMPILE_WARNING.php'; // E_COMPILE_WARNING (not working)
