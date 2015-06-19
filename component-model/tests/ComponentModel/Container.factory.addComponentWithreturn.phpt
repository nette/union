<?php

/**
 * Test: Nette\ComponentModel\Container component named factory 6.
 */

use Nette\ComponentModel\Container;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class TestClass extends Container
{

	public function createComponentB($name)
	{
		$this->addComponent($component = new self, $name);
		return $component;
	}

}


$a = new TestClass;
Assert::same('b', $a->getComponent('b')->getName());


Assert::exception(function () use ($a) {
	$a->getComponent('B')->getName();
}, 'InvalidArgumentException', "Component with name 'B' does not exist.");
