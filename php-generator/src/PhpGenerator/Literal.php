<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\PhpGenerator;


/**
 * PHP literal value.
 */
class Literal
{
	/**
	 * Creates a literal representing the creation of an object using the new operator.
	 */
	public static function new(string $_class, mixed ...$_args): self
	{
		return new self('new ' . $_class . '(...?:)', [$_args]);
	}


	/**
	 * Creates a literal representing the calling a method or function.
	 */
	public static function call(string $_function, mixed ...$_args): self
	{
		return new self($_function . '(...?:)', [$_args]);
	}


	public function __construct(
		private string $value,
		/** @var ?mixed[] */
		private ?array $args = null,
	) {
	}


	public function __toString(): string
	{
		return $this->formatWith(new Dumper);
	}


	/** @internal */
	public function formatWith(Dumper $dumper): string
	{
		return $this->args === null
			? $this->value
			: $dumper->format($this->value, ...$this->args);
	}
}
