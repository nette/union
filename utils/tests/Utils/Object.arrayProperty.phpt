<?php

/**
 * Test: Nette\Object array property.
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class TestClass extends Nette\Object
{
	private $items = [];

	public function & getItems()
	{
		return $this->items;
	}

	public function setItems(array $value)
	{
		$this->items = $value;
	}

}


$obj = new TestClass;
$obj->items[] = 'test';
Assert::same(['test'], $obj->items);
