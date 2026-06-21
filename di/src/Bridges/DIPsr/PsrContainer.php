<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Bridges\DIPsr;

use Nette\DI\Container;
use Psr\Container\ContainerInterface;
use function count, sprintf;


/**
 * Exposes a Nette DI container through the PSR-11 ContainerInterface.
 *
 * An identifier is resolved first as an autowired type, then as a service name. An ambiguous
 * type (multiple autowired services) is treated as not found.
 */
final class PsrContainer implements ContainerInterface
{
	public function __construct(
		private readonly Container $container,
	) {
	}


	public function get(string $id): object
	{
		$names = $this->container->findAutowired($id, preferredOnly: true);
		if (count($names) === 1) {
			return $this->container->getService($names[0]);
		} elseif (count($names) > 1) {
			natsort($names);
			throw new NotFoundException(sprintf("Multiple services of type '%s' found: %s.", $id, implode(', ', $names)));
		} elseif ($this->container->hasService($id)) {
			return $this->container->getService($id);
		}

		throw new NotFoundException(sprintf("Service '%s' not found.", $id));
	}


	public function has(string $id): bool
	{
		return count($this->container->findAutowired($id, preferredOnly: true)) === 1
			|| $this->container->hasService($id);
	}
}
