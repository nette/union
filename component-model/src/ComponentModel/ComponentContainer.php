<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette
 */

/*namespace Nette;*/



require_once dirname(__FILE__) . '/Component.php';

require_once dirname(__FILE__) . '/IComponentContainer.php';



/**
 * ComponentContainer is default implementation of IComponentContainer.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette
 *
 * @property-read \ArrayIterator $components
 */
class ComponentContainer extends Component implements IComponentContainer
{
	/** @var array of IComponent */
	private $components = array();

	/** @var IComponent|NULL */
	private $cloning;



	/********************* interface IComponentContainer ****************d*g**/



	/**
	 * Adds the specified component to the IComponentContainer.
	 * @param  IComponent
	 * @param  string
	 * @param  string
	 * @return void
	 * @throws \InvalidStateException
	 */
	public function addComponent(IComponent $component, $name, $insertBefore = NULL)
	{
		if ($name === NULL) {
			$name = $component->getName();
		}

		if (is_int($name)) {
			$name = (string) $name;

		} elseif (!is_string($name)) {
			throw new /*\*/InvalidArgumentException("Component name must be integer or string, " . gettype($name) . " given.");

		} elseif (!preg_match('#^[a-zA-Z0-9_]+$#', $name)) {
			throw new /*\*/InvalidArgumentException("Component name must be non-empty alphanumeric string, '$name' given.");
		}

		if (isset($this->components[$name])) {
			throw new /*\*/InvalidStateException("Component with name '$name' already exists.");
		}

		// check circular reference
		$obj = $this;
		do {
			if ($obj === $component) {
				throw new /*\*/InvalidStateException("Circular reference detected.");
			}
			$obj = $obj->getParent();
		} while ($obj !== NULL);

		// user checking
		$this->validateChildComponent($component);

		try {
			if (isset($this->components[$insertBefore])) {
				$tmp = array();
				foreach ($this->components as $k => $v) {
					if ($k === $insertBefore) $tmp[$name] = $component;
					$tmp[$k] = $v;
				}
				$this->components = $tmp;
			} else {
				$this->components[$name] = $component;
			}
			$component->setParent($this, $name);

		} catch (/*\*/Exception $e) {
			unset($this->components[$name]); // undo
			throw $e;
		}
	}



	/**
	 * Removes a component from the IComponentContainer.
	 * @param  IComponent
	 * @return void
	 */
	public function removeComponent(IComponent $component)
	{
		$name = $component->getName();
		if (!isset($this->components[$name]) || $this->components[$name] !== $component) {
			throw new /*\*/InvalidArgumentException("Component named '$name' is not located in this container.");
		}

		unset($this->components[$name]);
		$component->setParent(NULL);
	}



	/**
	 * Returns component specified by name or path.
	 * @param  string
	 * @param  bool   throw exception if component doesn't exist?
	 * @return IComponent|NULL
	 */
	final public function getComponent($name, $need = TRUE)
	{
		if (is_int($name)) {
			$name = (string) $name;

		} elseif (!is_string($name)) {
			throw new /*\*/InvalidArgumentException("Component name must be integer or string, " . gettype($name) . " given.");

		} else {
			$a = strpos($name, self::NAME_SEPARATOR);
			if ($a !== FALSE) {
				$ext = (string) substr($name, $a + 1);
				$name = substr($name, 0, $a);
			}

			if ($name === '') {
				throw new /*\*/InvalidArgumentException("Component or subcomponent name must not be empty string.");
			}
		}

		if (!isset($this->components[$name])) {
			$this->createComponent($name);
		}

		if (isset($this->components[$name])) {
			return $a === FALSE ? $this->components[$name] : $this->components[$name]->getComponent($ext, $need);

		} elseif ($need) {
			throw new /*\*/InvalidArgumentException("Component with name '$name' does not exist.");

		} else {
			return NULL;
		}
	}



	/**
	 * Component factory. Delegates the creation of components to a createComponent<Name> method.
	 * @param  string  component name
	 * @return void
	 */
	protected function createComponent($name)
	{
		$ucname = ucfirst($name);
		$method = 'createComponent' . $ucname;
		if ($ucname !== $name && method_exists($this, $method) && $this->getReflection()->getMethod($method)->getName() === $method) {
			$component = $this->$method($name);
			if ($component instanceof IComponent && $component->getParent() === NULL) {
				$this->addComponent($component, $name);
			}
		}
	}



	/**
	 * Iterates over a components.
	 * @param  bool    recursive?
	 * @param  string  class types filter
	 * @return \ArrayIterator
	 */
	final public function getComponents($deep = FALSE, $filterType = NULL)
	{
		$iterator = new RecursiveComponentIterator($this->components);
		if ($deep) {
			$deep = $deep > 0 ? /*\*/RecursiveIteratorIterator::SELF_FIRST : /*\*/RecursiveIteratorIterator::CHILD_FIRST;
			$iterator = new /*\*/RecursiveIteratorIterator($iterator, $deep);
		}
		if ($filterType) {
			/**/fixNamespace($filterType);/**/
			$iterator = new InstanceFilterIterator($iterator, $filterType);
		}
		return $iterator;
	}



	/**
	 * Descendant can override this method to disallow insert a child by throwing an \InvalidStateException.
	 * @param  IComponent
	 * @return void
	 * @throws \InvalidStateException
	 */
	protected function validateChildComponent(IComponent $child)
	{
	}



	/********************* cloneable, serializable ****************d*g**/



	/**
	 * Object cloning.
	 */
	public function __clone()
	{
		if ($this->components) {
			$oldMyself = reset($this->components)->getParent();
			$oldMyself->cloning = $this;
			foreach ($this->components as $name => $component) {
				$this->components[$name] = clone $component;
			}
			$oldMyself->cloning = NULL;
		}
		parent::__clone();
	}



	/**
	 * Is container cloning now?
	 * @return NULL|IComponent
	 * @internal
	 */
	public function isCloning()
	{
		return $this->cloning;
	}

}






/**
 * Recursive component iterator. See ComponentContainer::getComponents().
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette
 */
class RecursiveComponentIterator extends /*\*/RecursiveArrayIterator
{

	/**
	 * Has the current element has children?
	 * @return bool
	 */
	public function hasChildren()
	{
		return $this->current() instanceof IComponentContainer;
	}



	/**
	 * The sub-iterator for the current element.
	 * @return \RecursiveIterator
	 */
	public function getChildren()
	{
		return $this->current()->getComponents();
	}

}
