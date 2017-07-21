<?php

/**
 * Test: Nette\Forms\Controls\CsrfProtection.
 */

declare(strict_types=1);

use Nette\Forms\Controls\CsrfProtection;
use Nette\Forms\Form;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$_SERVER['REQUEST_METHOD'] = 'POST';


$form = new Form;

$input = $form->addProtection('Security token did not match. Possible CSRF attack.');

$form->fireEvents();

Assert::same(['This field is required.'], $form->getErrors());
Assert::null($input->getOption('rendered'));
Assert::match('<input type="hidden" name="_token_" value="%S%">', (string) $input->getControl());
Assert::true($input->getOption('rendered'));
Assert::same('hidden', $input->getOption('type'));

$input->setValue(null);
Assert::false(CsrfProtection::validateCsrf($input));

call_user_func([$input, 'Nette\Forms\Controls\BaseControl::setValue'], '12345678901234567890123456789012345678');
Assert::false(CsrfProtection::validateCsrf($input));

$value = $input->getControl()->value;
call_user_func([$input, 'Nette\Forms\Controls\BaseControl::setValue'], $value);
Assert::true(CsrfProtection::validateCsrf($input));

session_regenerate_id();
call_user_func([$input, 'Nette\Forms\Controls\BaseControl::setValue'], $value);
Assert::false(CsrfProtection::validateCsrf($input));



// protection is always the first
$form = new Form;
$form->addText('text');
$form->addProtection();
Assert::same([
	$form::PROTECTOR_ID => $form[$form::PROTECTOR_ID],
	'text' => $form['text'],
], (array) $form->getComponents());
