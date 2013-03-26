<?php

/**
 * Test: Tracy\Debugger E_RECOVERABLE_ERROR error.
 *
 * @author     David Grudl
 * @package    Tracy
 */

use Tracy\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$productionMode = FALSE;

Debugger::enable();



class TestClass
{

	function test1(array $val)
	{
	}


	function test2(TestClass $val)
	{
	}


	function __toString()
	{
		return FALSE;
	}


}


$obj = new TestClass;

Assert::exception(function() use ($obj) {
	// Invalid argument #1
	$obj->test1('hello');
}, 'Tracy\ErrorException', 'Argument 1 passed to TestClass::test1() must be %a% array, string given, called in %a%');

Assert::exception(function() use ($obj) {
	// Invalid argument #2
	$obj->test2('hello');
}, 'Tracy\ErrorException', 'Argument 1 passed to TestClass::test2() must be an instance of TestClass, string given, called in %a%');

Assert::exception(function() use ($obj) {
	// Invalid toString
	echo $obj;
}, 'Tracy\ErrorException', 'Method TestClass::__toString() must return a string value');
