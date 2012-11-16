<?php

/**
 * Test: Nette\Diagnostics\Debugger exception in HTML.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 * @assertCode 500
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = FALSE;
Debugger::$productionMode = FALSE;
header('Content-Type: text/html');

Debugger::enable();

function shutdown() {
	Assert::match(file_get_contents(__DIR__ . '/Debugger.exception.html.expect'), ob_get_clean());
}
Assert::handler('shutdown');



function first($arg1, $arg2)
{
	second(TRUE, FALSE);
}



function second($arg1, $arg2)
{
	third(array(1, 2, 3));
}


function third($arg1)
{
	throw new Exception('The my exception', 123);
}


define('MY_CONST', 123);

first(10, 'any string');
