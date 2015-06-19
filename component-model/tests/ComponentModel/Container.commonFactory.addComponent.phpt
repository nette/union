<?php

/**
 * Test: Nette\ComponentModel\Container component factory 2.
 */

use Nette\ComponentModel\Container;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class TestClass extends Container
{

	public function createComponent($name)
	{
		$this->addComponent(new self, $name);
	}

}


$a = new TestClass;
Assert::same('b', $a->getComponent('b')->getName());
