<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\PhpGenerator\Traits;

use Nette;
use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\Parameter;


/**
 * @internal
 */
trait FunctionLike
{
	private string $body = '';

	/** @var Parameter[] */
	private array $parameters = [];

	private bool $variadic = false;

	private ?string $returnType = null;

	private bool $returnReference = false;

	private bool $returnNullable = false;


	public function setBody(string $code, array $args = null): static
	{
		$this->body = $args === null
			? $code
			: (new Dumper)->format($code, ...$args);
		return $this;
	}


	public function getBody(): string
	{
		return $this->body;
	}


	public function addBody(string $code, array $args = null): static
	{
		$this->body .= ($args === null ? $code : (new Dumper)->format($code, ...$args)) . "\n";
		return $this;
	}


	/**
	 * @param  Parameter[]  $val
	 */
	public function setParameters(array $val): static
	{
		$this->parameters = [];
		foreach ($val as $v) {
			if (!$v instanceof Parameter) {
				throw new Nette\InvalidArgumentException('Argument must be Nette\PhpGenerator\Parameter[].');
			}
			$this->parameters[$v->getName()] = $v;
		}
		return $this;
	}


	/** @return Parameter[] */
	public function getParameters(): array
	{
		return $this->parameters;
	}


	/**
	 * @param  string  $name without $
	 */
	public function addParameter(string $name, $defaultValue = null): Parameter
	{
		$param = new Parameter($name);
		if (func_num_args() > 1) {
			$param->setDefaultValue($defaultValue);
		}
		return $this->parameters[$name] = $param;
	}


	/**
	 * @param  string  $name without $
	 */
	public function removeParameter(string $name): static
	{
		unset($this->parameters[$name]);
		return $this;
	}


	public function setVariadic(bool $state = true): static
	{
		$this->variadic = $state;
		return $this;
	}


	public function isVariadic(): bool
	{
		return $this->variadic;
	}


	public function setReturnType(?string $val): static
	{
		$this->returnType = $val;
		return $this;
	}


	public function getReturnType(): ?string
	{
		return $this->returnType;
	}


	public function setReturnReference(bool $state = true): static
	{
		$this->returnReference = $state;
		return $this;
	}


	public function getReturnReference(): bool
	{
		return $this->returnReference;
	}


	public function setReturnNullable(bool $state = true): static
	{
		$this->returnNullable = $state;
		return $this;
	}


	public function isReturnNullable(): bool
	{
		return $this->returnNullable;
	}


	/** @deprecated  use isReturnNullable() */
	public function getReturnNullable(): bool
	{
		return $this->returnNullable;
	}
}
