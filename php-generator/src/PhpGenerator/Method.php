<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\PhpGenerator;

use Nette;


/**
 * Class method.
 *
 * @property string|null $body
 */
final class Method
{
	use Nette\SmartObject;
	use Traits\FunctionLike;
	use Traits\NameAware;
	use Traits\VisibilityAware;
	use Traits\CommentAware;
	use Traits\AttributeAware;

	private bool $static = false;

	private bool $final = false;

	private bool $abstract = false;

	private bool $interface = false;


	public static function from(string|array $method): static
	{
		return (new Factory)->fromMethodReflection(Nette\Utils\Callback::toReflection($method));
	}


	public function __toString(): string
	{
		return (new Printer)->printMethod($this);
	}


	public function setBody(?string $code, array $args = null): static
	{
		$this->interface = $code === null;
		if ($code !== null) {
			$this->body = $args === null
				? $code
				: (new Dumper)->format($code, ...$args);
		}
		return $this;
	}


	public function getBody(): ?string
	{
		return $this->interface ? null : $this->body;
	}


	public function setStatic(bool $state = true): static
	{
		$this->static = $state;
		return $this;
	}


	public function isStatic(): bool
	{
		return $this->static;
	}


	public function setFinal(bool $state = true): static
	{
		$this->final = $state;
		return $this;
	}


	public function isFinal(): bool
	{
		return $this->final;
	}


	public function setAbstract(bool $state = true): static
	{
		$this->abstract = $state;
		return $this;
	}


	public function isAbstract(): bool
	{
		return $this->abstract;
	}


	/**
	 * @param  string  $name without $
	 */
	public function addPromotedParameter(string $name, $defaultValue = null): PromotedParameter
	{
		$param = new PromotedParameter($name);
		if (func_num_args() > 1) {
			$param->setDefaultValue($defaultValue);
		}
		return $this->parameters[$name] = $param;
	}


	/** @throws Nette\InvalidStateException */
	public function validate(): void
	{
		if ($this->abstract && ($this->final || $this->visibility === ClassType::VISIBILITY_PRIVATE)) {
			throw new Nette\InvalidStateException('Method cannot be abstract and final or private.');
		}
	}
}
