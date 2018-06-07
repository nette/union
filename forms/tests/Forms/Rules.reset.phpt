<?php

declare(strict_types=1);

use Nette\Forms\Form;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	$form = new Form;
	$input = $form->addText('text');
	$input->addCondition(false)
		->setRequired();

	Assert::count(1, iterator_to_array($input->getRules()));
	$input->getRules()->reset();

	Assert::count(0, iterator_to_array($input->getRules()));
});


test(function () {
	$form = new Form;
	$input1 = $form->addText('text1');
	$input2 = $form->addText('text2');
	$input2->addConditionOn($input1, $form::FILLED)
		->addRule($form::BLANK);

	Assert::count(0, iterator_to_array($input1->getRules()));
	Assert::count(1, iterator_to_array($input2->getRules()));
	$input2->getRules()->reset();

	Assert::count(0, iterator_to_array($input2->getRules()));
});
