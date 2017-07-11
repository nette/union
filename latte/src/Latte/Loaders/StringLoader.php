<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Latte\Loaders;

use Latte;


/**
 * Template loader.
 */
class StringLoader implements Latte\ILoader
{
	use Latte\Strict;

	/** @var array|NULL [name => content] */
	private $templates;


	public function __construct(array $templates = NULL)
	{
		$this->templates = $templates;
	}


	/**
	 * Returns template source code.
	 */
	public function getContent($name): string
	{
		if ($this->templates === NULL) {
			return $name;
		} elseif (isset($this->templates[$name])) {
			return $this->templates[$name];
		} else {
			throw new \RuntimeException("Missing template '$name'.");
		}
	}


	public function isExpired($name, $time): bool
	{
		return FALSE;
	}


	/**
	 * Returns referred template name.
	 */
	public function getReferredName($name, $referringName): string
	{
		if ($this->templates === NULL) {
			throw new \LogicException("Missing template '$name'.");
		}
		return $name;
	}


	/**
	 * Returns unique identifier for caching.
	 */
	public function getUniqueId($name): string
	{
		return $this->getContent($name);
	}
}
