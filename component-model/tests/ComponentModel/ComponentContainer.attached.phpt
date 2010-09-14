<?php

/**
 * Test: Nette\ComponentContainer::attached()
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\ComponentContainer;



require __DIR__ . '/../initialize.php';



class TestClass extends ComponentContainer implements ArrayAccess
{
	protected function attached($obj)
	{
		TestHelpers::note(get_class($this) . '::ATTACHED(' . get_class($obj) . ')');
	}

	protected function detached($obj)
	{
		TestHelpers::note(get_class($this) . '::detached(' . get_class($obj) . ')');
	}

	final public function offsetSet($name, $component)
	{
		$this->addComponent($component, $name);
	}

	final public function offsetGet($name)
	{
		return $this->getComponent($name, TRUE);
	}

	final public function offsetExists($name)
	{
		return $this->getComponent($name) !== NULL;
	}

	final public function offsetUnset($name)
	{
		$this->removeComponent($this->getComponent($name, TRUE));
	}
}


class A extends TestClass {}
class B extends TestClass {}
class C extends TestClass {}
class D extends TestClass {}
class E extends TestClass {}

$d = new D;
$d['e'] = new E;
$b = new B;
$b->monitor('a');
$b['c'] = new C;
$b['c']->monitor('a');
$b['c']['d'] = $d;

// 'a' becoming 'b' parent
$a = new A;
$a['b'] = $b;
Assert::same( array(
	'C::ATTACHED(A)',
	'B::ATTACHED(A)',
), TestHelpers::fetchNotes());



// removing 'b' from 'a'
unset($a['b']);
Assert::same( array(
	'C::detached(A)',
	'B::detached(A)',
), TestHelpers::fetchNotes());

// 'a' becoming 'b' parent
$a['b'] = $b;

Assert::same( 'b-c-d-e', $d['e']->lookupPath('A') );

Assert::true( $a['b-c'] === $b['c'] );
