<?php

/**
 * Test: Nette\Forms\Helpers::exportRules()
 */

declare(strict_types=1);

use Nette\Forms\Form;
use Nette\Forms\Helpers;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	$form = new Form;
	$input = $form->addText('text');
	$input->addRule(Form::FILLED, null, []);
	Assert::same([
		[
			'op' => ':filled',
			'msg' => 'This field is required.',
			'arg' => [],
		],
	], Helpers::exportRules($input->getRules()));
});


test(function () {
	$form = new Form;
	$input = $form->addText('text');
	$input->addRule(Form::EMAIL);
	Assert::same([
		['op' => ':email', 'msg' => 'Please enter a valid email address.'],
	], Helpers::exportRules($input->getRules()));
});


test(function () {
	$form = new Form;
	$input = $form->addText('text');
	$input->setRequired(false);
	$input->addRule(Form::EMAIL);
	Assert::same([
		['op' => 'optional'],
		['op' => ':email', 'msg' => 'Please enter a valid email address.'],
	], Helpers::exportRules($input->getRules()));
});


test(function () {
	$form = new Form;
	$input1 = $form->addText('text1');
	$input2 = $form->addText('text2');
	$input2->setRequired(false);
	$input2->addConditionOn($input1, Form::EMAIL)
		->setRequired(true)
		->addRule($form::EMAIL);
	$input2->addConditionOn($input1, Form::INTEGER)
		->setRequired(false)
		->addRule($form::EMAIL);

	Assert::same([
		['op' => 'optional'],
		[
			'op' => ':email',
			'rules' => [
				['op' => ':filled', 'msg' => 'This field is required.'],
				['op' => ':email', 'msg' => 'Please enter a valid email address.'],
			],
			'control' => 'text1',
		],
		[
			'op' => ':integer',
			'rules' => [
				['op' => 'optional'],
				['op' => ':email', 'msg' => 'Please enter a valid email address.'],
			],
			'control' => 'text1',
		],
	], Helpers::exportRules($input2->getRules()));
});
