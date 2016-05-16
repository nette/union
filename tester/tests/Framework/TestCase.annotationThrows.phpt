<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class MyException extends Exception
{
}

class MyTest extends Tester\TestCase
{
	/** @throws Exception */
	public function testThrows()
	{
		throw new Exception;
	}

	/** @throws Exception */
	public function testThrowsButDont()
	{
	}

	/** @throws Exception  With message */
	public function testThrowsMessage()
	{
		throw new Exception('With message');
	}

	/** @throws Exception */
	public function testFailAssertPass()
	{
		Assert::fail('failed');
	}

	/** @throws MyException */
	public function testThrowsBadClass()
	{
		throw new Exception;
	}

	/** @throws Exception  With message */
	public function testThrowsBadMessage()
	{
		throw new Exception('Bad message');
	}

	/** @throws E_NOTICE */
	public function testNotice()
	{
		$a++;
	}

	/** @throws E_NOTICE  Undefined variable: a */
	public function testNoticeMessage()
	{
		$a++;
	}

	/** @throws E_WARNING */
	public function testBadError()
	{
		$a++;
	}

	/** @throws E_NOTICE  With message */
	public function testNoticeBadMessage()
	{
		$a++;
	}

	// Without @throws
	public function testWithoutThrows()
	{
		throw new Exception;
	}

	public function dataProvider()
	{
		return [[1]];
	}

	/**
	 * @dataprovider dataProvider
	 * @throws Exception
	 */
	public function testThrowsWithDataprovider($x)
	{
	}

}


$test = new MyTest;
$test->runTest('testThrows');
$test->runTest('testThrowsMessage');

Assert::exception(function () use ($test) {
	$test->runTest('testThrowsButDont');
}, 'Tester\AssertException', 'Exception was expected, but none was thrown in testThrowsButDont()');

Assert::exception(function () use ($test) {
	$test->runTest('testFailAssertPass');
}, 'Tester\AssertException', 'failed in testFailAssertPass()');

Assert::exception(function () use ($test) {
	$test->runTest('testThrowsBadClass');
}, 'Tester\AssertException', 'MyException was expected but got Exception in testThrowsBadClass()');

Assert::exception(function () use ($test) {
	$test->runTest('testThrowsBadMessage');
}, 'Tester\AssertException', "Exception with a message matching 'With message' was expected but got 'Bad message' in testThrowsBadMessage()");

Assert::exception(function () use ($test) {
	$test->runTest('testWithoutThrows');
}, 'Exception');

Assert::exception(function () use ($test) {
	$test->runTest('testThrowsWithDataprovider');
}, 'Exception', 'Exception was expected, but none was thrown in testThrowsWithDataprovider(1)');

Assert::exception(function () use ($test) {
	$test->runTest('testUndefinedMethod');
}, 'Tester\TestCaseException', "Method 'testUndefinedMethod' does not exist.");

$test->runTest('testNotice');
$test->runTest('testNoticeMessage');

Assert::exception(function () use ($test) {
	$test->runTest('testBadError');
}, 'Tester\AssertException', 'E_WARNING was expected, but E_NOTICE (Undefined variable: a) was generated in %a%testBadError()');

Assert::exception(function () use ($test) {
	$test->runTest('testNoticeBadMessage');
}, 'Tester\AssertException', "E_NOTICE with a message matching 'With message' was expected but got 'Undefined variable: a' in testNoticeBadMessage()");
