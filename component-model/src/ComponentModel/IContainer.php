<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\ComponentModel;


/**
 * Containers are objects that logically contain zero or more IComponent components.
 */
interface IContainer extends IComponent
{
	/**
	 * Adds the component to the container.
	 * @param  string|int $name
	 * @return static
	 */
	function addComponent(IComponent $component, $name);

	/**
	 * Removes the component from the container.
	 */
	function removeComponent(IComponent $component): void;

	/**
	 * Returns component specified by name or path.
	 * @param  string|int
	 */
	function getComponent($name): ?IComponent;

	/**
	 * Iterates over descendants components.
	 */
	function getComponents(bool $deep = false, string $filterType = null): \Iterator;
}
