<?php

/**
 * Test: Nette\Object properties.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */




require __DIR__ . '/../bootstrap.php';



class TestClass extends Nette\Object
{
	public $foo;
}


$obj = new TestClass;
unset($obj->foo);
Assert::false( isset($obj->foo) );

// re-set
$obj->foo = 'hello';
Assert::same( 'hello', $obj->foo );


// double unset
$obj = new TestClass;
unset($obj->foo);
unset($obj->foo);


// reading of unset property
Assert::throws(function() {
	$obj = new TestClass;
	unset($obj->foo);
	$val = $obj->foo;
}, 'Nette\MemberAccessException', 'Cannot read an undeclared property TestClass::$foo.');
