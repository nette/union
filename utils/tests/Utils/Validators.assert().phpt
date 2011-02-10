<?php

/**
 * Test: Nette\Utils\Validators::assert()
 *
 * @author     David Grudl
 * @package    Nette\Utils
 * @subpackage UnitTests
 */

use Nette\Utils\Validators;



require __DIR__ . '/../bootstrap.php';



Assert::throws(function() {
	Validators::assert(TRUE, 'int');
}, 'Nette\Utils\AssertionException', "The variable expects to be int, boolean given.");

Assert::throws(function() {
	Validators::assert('1.0', 'int|float');
}, 'Nette\Utils\AssertionException', "The variable expects to be int or float, string given.");

Assert::throws(function() {
	Validators::assert(1, 'string|integer:2..5', 'variable');
}, 'Nette\Utils\AssertionException', "The variable expects to be string or integer in range 2..5, integer given.");
