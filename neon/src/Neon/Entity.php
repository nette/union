<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Neon;


/**
 * Representation of NEON entity 'foo(bar=1)'
 */
final class Entity extends \stdClass
{
	public mixed $value;

	public array $attributes;


	public function __construct($value, array $attrs = [])
	{
		$this->value = $value;
		$this->attributes = $attrs;
	}


	public static function __set_state(array $properties)
	{
		return new self($properties['value'], $properties['attributes']);
	}
}
