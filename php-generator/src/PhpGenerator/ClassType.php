<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Utils\PhpGenerator;

use Nette,
	Nette\Utils\Strings;



/**
 * Class/Interface/Trait description.
 *
 * @author     David Grudl
 *
 * @method ClassType setName(string $name)
 * @method ClassType setType(string $type)
 * @method ClassType setFinal(bool $on)
 * @method ClassType setAbstract(bool $on)
 * @method ClassType addExtend(string $class)
 * @method ClassType addImplement(string $interface)
 * @method ClassType addTrait(string $trait)
 * @method ClassType addDocument(string $doc)
 */
class ClassType extends Nette\Object
{
	/** @var string */
	public $name;

	/** @var string  class|interface|trait */
	public $type = 'class';

	/** @var bool */
	public $final;

	/** @var bool */
	public $abstract;

	/** @var string[] */
	public $extends = array();

	/** @var string[] */
	public $implements = array();

	/** @var string[] */
	public $traits = array();

	/** @var string[] */
	public $documents = array();

	/** @var mixed[] name => value */
	public $consts = array();

	/** @var Property[] name => Property */
	public $properties = array();

	/** @var Method[] name => Method */
	public $methods = array();


	/** @return Class */
	public static function from($from)
	{
		$from = $from instanceof \ReflectionClass ? $from : new \ReflectionClass($from);
		$class = new static(/*5.2*PHP_VERSION_ID < 50300 ? $from->getName() : */$from->getShortName());
		$class->type = $from->isInterface() ? 'interface' : (PHP_VERSION_ID >= 50400 && $from->isTrait() ? 'trait' : 'class');
		$class->final = $from->isFinal();
		$class->abstract = $from->isAbstract() && $class->type === 'class';
		$class->implements = $from->getInterfaceNames();
		$class->documents = preg_replace('#^\s*\* ?#m', '', trim($from->getDocComment(), "/* \r\n"));
		$namespace = /*5.2*PHP_VERSION_ID < 50300 ? NULL : */$from->getNamespaceName();
		if ($from->getParentClass()) {
			$class->extends = $from->getParentClass()->getName();
			if ($namespace) {
				$class->extends = Strings::startsWith($class->extends, "$namespace\\") ? substr($class->extends, strlen($namespace) + 1) : '\\' . $class->extends;
			}
			$class->implements = array_diff($class->implements, $from->getParentClass()->getInterfaceNames());
		}
		if ($namespace) {
			foreach ($class->implements as & $interface) {
				$interface = Strings::startsWith($interface, "$namespace\\") ? substr($interface, strlen($namespace) + 1) : '\\' . $interface;
			}
		}
		foreach ($from->getProperties() as $prop) {
			$class->properties[] = Property::from($prop);
		}
		foreach ($from->getMethods() as $method) {
			if ($method->getDeclaringClass() == $from) { // intentionally ==
				$class->methods[] = Method::from($method);
			}
		}
		return $class;
	}



	public function __construct($name)
	{
		$this->name = $name;
	}



	/** @return ClassType */
	public function addConst($name, $value)
	{
		$this->consts[$name] = $value;
		return $this;
	}



	/** @return Property */
	public function addProperty($name, $value = NULL)
	{
		$property = new Property;
		return $this->properties[$name] = $property->setName($name)->setValue($value);
	}



	/** @return Method */
	public function addMethod($name)
	{
		$method = new Method;
		if ($this->type === 'interface') {
			$method->setVisibility('')->setBody(FALSE);
		} else {
			$method->setVisibility('public');
		}
		return $this->methods[$name] = $method->setName($name);
	}



	public function __call($name, $args)
	{
		return Nette\ObjectMixin::callProperty($this, $name, $args);
	}



	/** @return string  PHP code */
	public function __toString()
	{
		$consts = array();
		foreach ($this->consts as $name => $value) {
			$consts[] = "const $name = " . Helpers::dump($value) . ";\n";
		}
		$properties = array();
		foreach ($this->properties as $property) {
			$properties[] = ($property->documents ? str_replace("\n", "\n * ", "/**\n" . implode("\n", (array) $property->documents)) . "\n */\n" : '')
				. $property->visibility . ($property->static ? ' static' : '') . ' $' . $property->name
				. ($property->value === NULL ? '' : ' = ' . Helpers::dump($property->value))
				. ";\n";
		}
		return Strings::normalize(
			($this->documents ? str_replace("\n", "\n * ", "/**\n" . implode("\n", (array) $this->documents)) . "\n */\n" : '')
			. ($this->abstract ? 'abstract ' : '')
			. ($this->final ? 'final ' : '')
			. $this->type . ' '
			. $this->name . ' '
			. ($this->extends ? 'extends ' . implode(', ', (array) $this->extends) . ' ' : '')
			. ($this->implements ? 'implements ' . implode(', ', (array) $this->implements) . ' ' : '')
			. "\n{\n\n"
			. Strings::indent(
				($this->traits ? "use " . implode(', ', (array) $this->traits) . ";\n\n" : '')
				. ($this->consts ? implode('', $consts) . "\n\n" : '')
				. ($this->properties ? implode("\n", $properties) . "\n\n" : '')
				. implode("\n\n\n", $this->methods), 1)
			. "\n\n}") . "\n";
	}

}
