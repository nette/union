<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\DI\Extensions;

use Nette;
use Nette\DI\Container;
use Nette\DI\DynamicParameter;
use Nette\PhpGenerator\Method;


/**
 * Parameters.
 */
final class ParametersExtension extends Nette\DI\CompilerExtension
{
	/** @var string[] */
	public array $dynamicParams = [];

	/** @var string[][] */
	public array $dynamicValidators = [];
	private array $compilerConfig;


	public function __construct(array &$compilerConfig)
	{
		$this->compilerConfig = &$compilerConfig;
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$params = array_fill_keys($this->dynamicParams, new DynamicParameter('')) + $this->config;

		$builder->parameters = Nette\DI\Helpers::expand($params, $params, recursive: true);
		$this->compilerConfig = Nette\DI\Helpers::expand($this->compilerConfig, $builder->parameters);
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$builder = $this->getContainerBuilder();
		$dynamicParams = $this->dynamicParams;
		foreach ($builder->parameters as $key => $value) {
			$value = [$value];
			array_walk_recursive($value, function ($val) use (&$dynamicParams, $key): void {
				if ($val instanceof DynamicParameter || $val instanceof Nette\DI\Definitions\Statement) {
					$dynamicParams[] = $key;
				}
			});
		}
		$dynamicParams = array_values(array_unique($dynamicParams));

		$method = Method::from([Container::class, 'getStaticParameters'])
			->addBody('return ?;', [array_diff_key($builder->parameters, array_flip($dynamicParams))]);
		$class->addMember($method);

		if (!$dynamicParams) {
			return;
		}

		$resolver = new Nette\DI\Resolver($builder);
		$generator = new Nette\DI\PhpGenerator($builder);
		$method = $class->inheritMethod('getDynamicParameter');
		$method->addBody('return match($key) {');
		foreach ($dynamicParams as $key) {
			$value = Nette\DI\Helpers::expand($this->config[$key] ?? null, $builder->parameters);
			$value = $resolver->completeArguments(Nette\DI\Helpers::filterArguments([$value]));
			$method->addBody("\t? => ?,", [$key, $generator->convertArguments($value)[0]]);
		}
		$method->addBody("\tdefault => parent::getDynamicParameter(\$key),\n};");

		$method = $class->inheritMethod('getParameters');
		$method->addBody('array_map(function ($key) { $this->getParameter($key); }, ?);', [$dynamicParams]);
		$method->addBody('return parent::getParameters();');

		foreach ($this->dynamicValidators as [$param, $expected, $path]) {
			if ($param instanceof DynamicParameter) {
				$this->initialization->addBody(
					'Nette\Utils\Validators::assert(?, ?, ?);',
					[$param, $expected, "dynamic parameter used in '" . implode("\u{a0}›\u{a0}", $path) . "'"],
				);
			}
		}
	}
}
