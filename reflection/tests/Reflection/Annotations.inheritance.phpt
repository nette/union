<?php

/**
 * Test: Nette\Reflection\AnnotationsParser inheritance.
 */

use Nette\Reflection;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


interface IA
{
	/** This is IA */
	public function __construct();

	/**
	 * This is IA
	 * @return mixed
	 * @author John
	 */
	public function foo();
}

class A implements IA
{
	/** @inheritdoc */
	public function __construct()
	{
	}

	/** @inheritdoc */
	public function foo()
	{
	}

	/** This is A */
	private function bar()
	{
	}

	/** @inheritdoc */
	public function foobar()
	{
	}
}

class B extends A
{
	public function __construct()
	{
	}

	/** This is B */
	public function foo()
	{
	}

	private function bar()
	{
	}
}


// constructors
$method = new Reflection\Method('B', '__construct');
Assert::null($method->getAnnotation('description'));

$method = new Reflection\Method('A', '__construct');
Assert::same('This is IA', $method->getAnnotation('description'));


// public method
$method = new Reflection\Method('B', 'foo');
Assert::same('This is B', $method->getAnnotation('description'));
Assert::same('mixed', $method->getAnnotation('return'));
Assert::null($method->getAnnotation('author'));

$method = new Reflection\Method('A', 'foo');
Assert::same('This is IA', $method->getAnnotation('description'));
Assert::same('mixed', $method->getAnnotation('return'));
Assert::null($method->getAnnotation('author'));

$method = new Reflection\Method('IA', 'foo');
Assert::same('This is IA', $method->getAnnotation('description'));
Assert::same('mixed', $method->getAnnotation('return'));
Assert::same('John', $method->getAnnotation('author'));


// private method
$method = new Reflection\Method('B', 'bar');
Assert::null($method->getAnnotation('description'));


// @inheritdoc
$method = new Reflection\Method('B', 'foobar');
Assert::null($method->getAnnotation('description'));
