<?php

/**
 * Test: Nette\Forms\Container::validate().
 */

use Nette\Forms\Form,
	Nette\Forms\Container,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

$form = new Form;
$form->addText('name')->addRule($form::NUMERIC);

$form->onValidate[] = function (Container $container) {
	$container['name']->addError('fail 1');
};

$container = $form->addContainer('cont');
$container->addText('name');
$container->onValidate[] = function (Container $container) {
	$container['name']->addError('fail 2');
};

$form->setValues(array('name' => "invalid*input"));
$form->validate();

Assert::same(array(
	'Please enter a valid integer.',
	'fail 1',
	'fail 2'
), $form->getErrors());
