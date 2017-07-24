<?php

/**
 * Test: Nette\Forms\Controls\HiddenField.
 */

declare(strict_types=1);

use Nette\Forms\Form;
use Nette\Utils\Html;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class Translator implements Nette\Localization\ITranslator
{
	public function translate($s, int $count = null): string
	{
		return strtoupper($s);
	}
}


test(function () {
	$form = new Form;
	$input = $form->addHidden('hidden', 'value');

	Assert::null($input->getLabel());
	Assert::type(Html::class, $input->getControl());
	Assert::same('<input type="hidden" name="hidden" value="value">', (string) $input->getControl());
});


test(function () { // no validation rules
	$form = new Form;
	$input = $form->addHidden('hidden')->setRequired('required');

	Assert::same('<input type="hidden" name="hidden" value="">', (string) $input->getControl());
});


test(function () { // container
	$form = new Form;
	$container = $form->addContainer('container');
	$input = $container->addHidden('hidden');

	Assert::same('<input type="hidden" name="container[hidden]" value="">', (string) $input->getControl());
});


test(function () { // forced ID
	$form = new Form;
	$input = $form->addHidden('hidden')->setRequired('required');
	$input->setHtmlId($input->getHtmlId());

	Assert::same('<input type="hidden" name="hidden" id="frm-hidden" value="">', (string) $input->getControl());
});


test(function () { // rendering options
	$form = new Form;
	$input = $form->addHidden('hidden');

	Assert::same('hidden', $input->getOption('type'));

	Assert::null($input->getOption('rendered'));
	$input->getControl();
	Assert::true($input->getOption('rendered'));
});
