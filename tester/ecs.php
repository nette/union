<?php

/**
 * Rules for Nette Coding Standard
 * https://github.com/nette/coding-standard
 */

declare(strict_types=1);


return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$containerConfigurator->import(PRESET_DIR . '/php71.php');

	$parameters = $containerConfigurator->parameters();

	$parameters->set('exclude_paths', [
		'fixtures*/*',
		'tests/Runner/*/*',
		'vendor/*',
	]);

	$parameters->set('skip', [
		PHP_CodeSniffer\Standards\PSR1\Sniffs\Methods\CamelCapsMethodNameSniff::class => [
			'src/Framework/FileMock.php',
			'src/Framework/FileMutator.php',
		],
	]);
};
