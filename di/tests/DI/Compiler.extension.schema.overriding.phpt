<?php

/**
 * Test: Nette\DI\CompilerExtension and schema overriding
 */

declare(strict_types=1);

use Nette\Schema\Expect;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class FooExtension extends Nette\DI\CompilerExtension
{
	public $loadedConfig;


	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::structure([
			'mapping' => Expect::arrayOf('string'),
		]);
	}


	public function loadConfiguration()
	{
		$this->loadedConfig = $this->config;
	}
}

test('Merging config', function () {
	$compiler = new Nette\DI\Compiler;
	$compiler->addExtension('foo', $foo = new FooExtension);
	$compiler->addConfig([
		'foo' => [
			'mapping' => ['foo'],
		],
	]);
	createContainer($compiler, '
	foo:
		mapping: [bar]
	');
	Assert::equal((object) ['mapping' => ['foo', 'bar']], $foo->loadedConfig);
});

test('Prevent merging config', function () {
	$compiler = new Nette\DI\Compiler;
	$compiler->addExtension('foo', $foo = new FooExtension);
	$compiler->addConfig([
		'foo' => [
			'mapping' => ['foo'],
		],
	]);
	createContainer($compiler, '
	foo:
		mapping!: [bar]
	');
	Assert::equal((object) ['mapping' => ['bar']], $foo->loadedConfig);
});

test('Prevent merging config with no predefined parameters', function () {
	$compiler = new Nette\DI\Compiler;
	$compiler->addExtension('foo', $foo = new FooExtension);
	createContainer($compiler, '
	foo:
		mapping!: [bar]
	');
	Assert::equal((object) ['mapping' => ['bar']], $foo->loadedConfig);
});
