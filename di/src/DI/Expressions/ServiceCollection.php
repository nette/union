<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\DI\Expressions;

use Nette\DI\Expression;
use Nette\DI\PhpGenerator;
use Nette\DI\Resolver;


/**
 * Array of all services matching a criterion.
 */
final class ServiceCollection extends Expression
{
	public function __construct(
		/** @var list<string> */
		public readonly array $types = [],
		/** @var list<string> */
		public readonly array $tags = [],
		/** @var list<Reference>|null resolved references, filled by complete() */
		public readonly ?array $references = null,
	) {
	}


	public function complete(Resolver $resolver): static
	{
		$builder = $resolver->getContainerBuilder();
		$current = $resolver->getCurrentService()?->getName();
		$references = [];
		foreach ($this->types as $type) {
			foreach ($builder->findAutowired($type) as $name => $foo) {
				if ($name !== $current) {
					$references[] = (new Reference($name))->complete($resolver);
				}
			}
		}

		foreach ($this->tags as $tag) {
			foreach ($builder->findByTag($tag) as $name => $foo) {
				if ($name !== $current) {
					$references[] = (new Reference($name))->complete($resolver);
				}
			}
		}

		return new self($this->types, $this->tags, $references);
	}


	public function generateCode(PhpGenerator $generator): string
	{
		return $generator->formatPhp('?', [$this->references ?? []]);
	}


	public function transformValues(callable $cb): static
	{
		return new self($cb($this->types), $cb($this->tags), $this->references);
	}
}
