<?php

declare(strict_types=1);

/**
 * Trait1
 */
trait Trait1
{
	public $x1;


	public function f1()
	{
		echo 'Trait1::f1';
	}
}


trait Trait2
{
	use Trait1;

	protected $x2;


	public function f2()
	{
		echo 'Trait2::f2';
	}
}


class ParentClass
{
	public $x1;


	public function f1()
	{
		echo 'ParentClass::f1';
	}
}


class Class1 extends ParentClass
{
	use Trait2;
}


class Class2 extends ParentClass
{
	use Trait2;

	public $x1;


	public function f1()
	{
		echo 'Class2::f1';
	}
}


class Class3 extends ParentClass
{
	use Trait2 {
		Trait2::f1 as aliased;
	}

	public $x1;


	public function f1()
	{
		echo 'Class3::f1';
	}
}


class Class4 extends ParentClass
{
	use Trait2 {
		Trait2::f1 as aliased;
	}


	public function aliased()
	{
		echo 'Class4::aliased';
	}
}


trait Trait1b
{
	public function f1()
	{
		echo 'Trait1b::f1';
	}
}


class Class5
{
	use Trait1, Trait1b {
		Trait1b::f1 insteadof Trait1;
	}
}
