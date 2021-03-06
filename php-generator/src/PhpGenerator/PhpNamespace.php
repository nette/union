<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\PhpGenerator;

use Nette;
use Nette\InvalidStateException;
use Nette\Utils\Strings;


/**
 * Namespaced part of a PHP file.
 *
 * Generates:
 * - namespace statement
 * - variable amount of use statements
 * - one or more class declarations
 */
final class PhpNamespace
{
	use Nette\SmartObject;

	private const KEYWORDS = [
		'string' => 1, 'int' => 1, 'float' => 1, 'bool' => 1, 'array' => 1, 'object' => 1,
		'callable' => 1, 'iterable' => 1, 'void' => 1, 'self' => 1, 'parent' => 1, 'static' => 1,
		'mixed' => 1, 'null' => 1, 'false' => 1,
	];

	private string $name;

	private bool $bracketedSyntax = false;

	/** @var string[] */
	private array $uses = [];

	/** @var ClassType[] */
	private array $classes = [];


	public function __construct(string $name)
	{
		if ($name !== '' && !Helpers::isNamespaceIdentifier($name)) {
			throw new Nette\InvalidArgumentException("Value '$name' is not valid name.");
		}
		$this->name = $name;
	}


	public function getName(): string
	{
		return $this->name;
	}


	/**
	 * @internal
	 */
	public function setBracketedSyntax(bool $state = true): static
	{
		$this->bracketedSyntax = $state;
		return $this;
	}


	public function hasBracketedSyntax(): bool
	{
		return $this->bracketedSyntax;
	}


	/** @deprecated  use hasBracketedSyntax() */
	public function getBracketedSyntax(): bool
	{
		return $this->bracketedSyntax;
	}


	/**
	 * @throws InvalidStateException
	 */
	public function addUse(string $name, string $alias = null, string &$aliasOut = null): static
	{
		$name = ltrim($name, '\\');
		if ($alias === null && $this->name === Helpers::extractNamespace($name)) {
			$alias = Helpers::extractShortName($name);
		}
		if ($alias === null) {
			$path = explode('\\', $name);
			$counter = null;
			do {
				if (empty($path)) {
					$counter++;
				} else {
					$alias = array_pop($path) . $alias;
				}
			} while (isset($this->uses[$alias . $counter]) && $this->uses[$alias . $counter] !== $name);
			$alias .= $counter;

		} elseif (isset($this->uses[$alias]) && $this->uses[$alias] !== $name) {
			throw new InvalidStateException(
				"Alias '$alias' used already for '{$this->uses[$alias]}', cannot use for '{$name}'.",
			);
		}

		$aliasOut = $alias;
		$this->uses[$alias] = $name;
		asort($this->uses);
		return $this;
	}


	/** @return string[] */
	public function getUses(): array
	{
		return $this->uses;
	}


	public function unresolveUnionType(string $type): string
	{
		return implode('|', array_map([$this, 'unresolveName'], explode('|', $type)));
	}


	public function unresolveName(string $name): string
	{
		if (isset(self::KEYWORDS[strtolower($name)]) || $name === '') {
			return $name;
		}
		$name = ltrim($name, '\\');
		$res = null;
		$lower = strtolower($name);
		foreach ($this->uses as $alias => $original) {
			if (Strings::startsWith($lower . '\\', strtolower($original) . '\\')) {
				$short = $alias . substr($name, strlen($original));
				if (!isset($res) || strlen($res) > strlen($short)) {
					$res = $short;
				}
			}
		}

		if (!$res && Strings::startsWith($lower, strtolower($this->name) . '\\')) {
			return substr($name, strlen($this->name) + 1);
		} else {
			return $res ?: ($this->name ? '\\' : '') . $name;
		}
	}


	public function add(ClassType $class): static
	{
		$name = $class->getName();
		if ($name === null) {
			throw new Nette\InvalidArgumentException('Class does not have a name.');
		}
		$this->addUse($this->name . '\\' . $name);
		$this->classes[$name] = $class;
		return $this;
	}


	public function addClass(string $name): ClassType
	{
		$this->add($class = new ClassType($name, $this));
		return $class;
	}


	public function addInterface(string $name): ClassType
	{
		return $this->addClass($name)->setInterface();
	}


	public function addTrait(string $name): ClassType
	{
		return $this->addClass($name)->setTrait();
	}


	/** @return ClassType[] */
	public function getClasses(): array
	{
		return $this->classes;
	}


	public function __toString(): string
	{
		return (new Printer)->printNamespace($this);
	}
}
