<?php

/**
 * @exitCode 255
 * @outputMatch #^Test::setUp,Exception: setUp\s+in#
 */

require __DIR__ . '/../bootstrap.php';

Tester\Environment::$useColors = FALSE;


class Test extends Tester\TestCase
{
	protected function setUp()
	{
		echo __METHOD__ . ',';
		throw new Exception('setUp');
	}

	public function testMe()
	{
		echo __METHOD__ . ',';
		throw new Exception('testMe');
	}

	protected function tearDown()
	{
		echo __METHOD__ . ',';
		throw new Exception('tearDown');
	}
}

(new Test)->run();
