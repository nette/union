<?php

/**
 * Test: Nette\ComponentModel\Container component factory 3.
 */

use Nette\ComponentModel\Container;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class TestClass extends Container
{

	public function createComponent($name)
	{
		$this->addComponent($component = new self, $name);
		return $component;
	}

}


$a = new TestClass;
Assert::same('b', $a->getComponent('b')->getName());
