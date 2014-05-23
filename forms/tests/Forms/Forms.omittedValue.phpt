<?php

/**
 * Test: Nette\Forms ignored input.
 */

use Nette\Forms\Form,
	Nette\Utils\ArrayHash,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$form = new Form('name');
$form->addProtection();
$form->addText('input');
$form->addText('omittedInput')
	->setOmitted();

Assert::same(array('input' => ''), $form->getValues(TRUE));
