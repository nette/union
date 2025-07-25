<?php

declare(strict_types=1);

use Nette\Assets\AssetNotFoundException;
use Nette\Assets\FilesystemMapper;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


touch(getTempDir() . '/test.txt', 2_700_000_000);

test('Basic mapper functionality', function () {
	$mapper = new FilesystemMapper('http://example.com/assets', getTempDir());
	$asset = $mapper->getAsset('test.txt');

	Assert::same('http://example.com/assets/test.txt?v=2700000000', $asset->url);
	Assert::same(getTempDir() . '/test.txt', $asset->file);
});


test('Non-existent file version handling', function () {
	$mapper = new FilesystemMapper('http://example.com/assets', getTempDir());
	Assert::exception(
		fn() => $mapper->getAsset('missing.txt'),
		AssetNotFoundException::class,
		"Asset file 'missing.txt' not found at path: '" . getTempDir() . "/missing.txt'",
	);
});


test('Mandatory extension autodetection', function () {
	$mapper = new FilesystemMapper(
		'http://example.com/assets',
		__DIR__ . '/fixtures',
		['gif', 'jpg'],
	);

	Assert::exception(
		fn() => $mapper->getAsset('image.gif'),
		AssetNotFoundException::class,
		"Asset file 'image.gif' not found at path: '" . __DIR__ . "/fixtures/image.gif.gif'",
	);

	$gif = $mapper->getAsset('image');
	Assert::match('http://example.com/assets/image.gif?v=%d%', $gif->url);

	Assert::exception(
		fn() => $mapper->getAsset('missing'),
		AssetNotFoundException::class,
	);

	Assert::exception(
		fn() => $mapper->getAsset('subdir'),
		AssetNotFoundException::class,
	);
});


test('Optional extension autodetection', function () {
	$mapper = new FilesystemMapper(
		'http://example.com/assets',
		__DIR__ . '/fixtures',
		['gif', 'jpg', ''],
	);

	$exact = $mapper->getAsset('image.gif');
	Assert::match('http://example.com/assets/image.gif?v=%d%', $exact->url);

	$gif = $mapper->getAsset('image');
	Assert::match('http://example.com/assets/image.gif?v=%d%', $gif->url);

	Assert::exception(
		fn() => $mapper->getAsset('missing'),
		AssetNotFoundException::class,
	);
});


test('Option validation', function () {
	$mapper = new FilesystemMapper('http://example.com/assets', __DIR__ . '/fixtures');

	Assert::exception(
		fn() => $mapper->getAsset('test.txt', ['invalid' => true]),
		InvalidArgumentException::class,
		'Unsupported asset options: invalid',
	);
});
