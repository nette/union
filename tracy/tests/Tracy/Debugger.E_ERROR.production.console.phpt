<?php

/**
 * Test: Nette\Diagnostics\Debugger E_ERROR in production & console mode.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = TRUE;
Debugger::$productionMode = TRUE;

Debugger::enable();

function shutdown() {
	Assert::match('ERROR:%A%', ob_get_clean());
	die(0);
}
Assert::handler('shutdown');



missing_funcion();
