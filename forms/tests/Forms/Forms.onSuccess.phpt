<?php

/**
 * Test: Nette\Forms onSuccess.
 */

use Nette\Forms\Form;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$_SERVER['REQUEST_METHOD'] = 'POST';

$called = [];
$form = new Form;
$form->addText('name');
$form->addSubmit('submit');
$form->onSuccess[] = function () use (&$called) {
	$called[] = 1;
};
$form->onSuccess[] = function ($form) use (&$called) {
	$called[] = 2;
	$form['name']->addError('error');
};
$form->onSuccess[] = function () use (&$called) {
	$called[] = 3;
};
$form->onSuccess[] = function () use (&$called) {
	$called[] = 4;
};
$form->onError[] = function () use (&$called) {
	$called[] = 'err';
};
$form->fireEvents();
Assert::same([1, 2, 'err'], $called);


$called = [];
$form = new Form;
$form->addText('name');
$form->addSubmit('submit');
$form->onSuccess[] = function () use (&$called) {
	$called[] = 1;
};
$form->onSuccess[] = function ($form) use (&$called) {
	$called[] = 2;
	$form['name']->addError('error');
};
$form->onError[] = function () use (&$called) {
	$called[] = 'err';
};
$form->fireEvents();
Assert::same([1, 2, 'err'], $called);


Assert::exception(function () {
	$form = new Form;
	$form->onSuccess = TRUE;
	$form->fireEvents();
}, Nette\UnexpectedValueException::class, 'Property Form::$onSuccess must be array or Traversable, boolean given.');
