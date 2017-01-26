<?php

/**
 * Test: Nette\ComponentModel\Container cloning.
 */

declare(strict_types=1);

use Nette\ComponentModel\Container;
use Nette\ComponentModel\IComponent;
use Nette\ComponentModel\IContainer;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class TestClass extends Container implements ArrayAccess
{
	function attached(IComponent $obj): void
	{
		Notes::add(get_class($this) . '::ATTACHED(' . get_class($obj) . ')');
	}

	function detached(IComponent $obj): void
	{
		Notes::add(get_class($this) . '::detached(' . get_class($obj) . ')');
	}

	function offsetSet($name, $component)
	{
		$this->addComponent($component, $name);
	}

	function offsetGet($name)
	{
		return $this->getComponent($name, TRUE);
	}

	function offsetExists($name)
	{
		return $this->getComponent($name) !== NULL;
	}

	function offsetUnset($name)
	{
		$this->removeComponent($this->getComponent($name, TRUE));
	}
}


function export($obj) {
	$res = ['(' . get_class($obj) . ')' => $obj->getName()];
	if ($obj instanceof IContainer) {
		foreach ($obj->getComponents() as $name => $child) {
			$res['children'][$name] = export($child);
		}
	}
	return $res;
};


class A extends TestClass {}
class B extends TestClass {}
class C extends TestClass {}
class D extends TestClass {}
class E extends TestClass {}

$a = new A;
$a['b'] = new B;
$a['b']['c'] = new C;
$a['b']['c']['d'] = new D;
$a['b']['c']['d']['e'] = new E;

$a['b']->monitor('a');
$a['b']->monitor('a');
$a['b']['c']->monitor('a');

Assert::same([
	'B::ATTACHED(A)',
	'C::ATTACHED(A)',
], Notes::fetch());

Assert::same('b-c-d-e', $a['b']['c']['d']['e']->lookupPath('A', FALSE));


// ==> clone 'c'
$dolly = clone $a['b']['c'];

Assert::same([
	'C::detached(A)',
], Notes::fetch());

Assert::null($dolly['d']['e']->lookupPath('A', FALSE));

Assert::same('d-e', $dolly['d']['e']->lookupPath('C', FALSE));


// ==> clone 'b'
$dolly = clone $a['b'];

Assert::same([
	'C::detached(A)',
	'B::detached(A)',
], Notes::fetch());


// ==> a['dolly'] = 'b'
$a['dolly'] = $dolly;

Assert::same([
	'C::ATTACHED(A)',
	'B::ATTACHED(A)',
], Notes::fetch());

Assert::same([
	'(A)' => NULL,
	'children' => [
		'b' => [
			'(B)' => 'b',
			'children' => [
				'c' => [
					'(C)' => 'c',
					'children' => [
						'd' => [
							'(D)' => 'd',
							'children' => [
								'e' => [
									'(E)' => 'e',
								],
							],
						],
					],
				],
			],
		],
		'dolly' => [
			'(B)' => 'dolly',
			'children' => [
				'c' => [
					'(C)' => 'c',
					'children' => [
						'd' => [
							'(D)' => 'd',
							'children' => [
								'e' => [
									'(E)' => 'e',
								],
							],
						],
					],
				],
			],
		],
	],
], export($a));
