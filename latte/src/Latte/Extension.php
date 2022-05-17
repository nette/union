<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Latte;


/**
 * Latte extension.
 */
abstract class Extension
{
	/**
	 * Initializes before template is compiler.
	 */
	public function beforeCompile(): void
	{
	}


	/**
	 * Returns a list of parsers for Latte tags.
	 * @return array<string, callable(Compiler\Tag, Compiler\TemplateParser): (Compiler\Node|\Generator|void)|\stdClass>
	 */
	public function getTags(): array
	{
		return [];
	}


	/**
	 * Returns a list of |filters.
	 * @return array<string, callable>
	 */
	public function getFilters(): array
	{
		return [];
	}


	/**
	 * Returns a list of functions used in templates.
	 * @return array<string, callable>
	 */
	public function getFunctions(): array
	{
		return [];
	}


	/**
	 * Returns a list of providers.
	 * @return array<mixed>
	 */
	public function getProviders(): array
	{
		return [];
	}


	/**
	 * Initializes before template is rendered.
	 */
	public function beforeRender(Engine $engine): void
	{
	}


	public static function order(callable $subject, array|string $before = [], array|string $after = []): \stdClass
	{
		return (object) get_defined_vars();
	}
}
