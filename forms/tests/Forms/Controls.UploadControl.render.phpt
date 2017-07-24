<?php

/**
 * Test: Nette\Forms\Controls\UploadControl.
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
	$input = $form->addUpload('file', 'Label');

	Assert::type(Html::class, $input->getLabel());
	Assert::same('<label for="frm-file">Label</label>', (string) $input->getLabel());
	Assert::same('<label for="frm-file">Another label</label>', (string) $input->getLabel('Another label'));

	Assert::type(Html::class, $input->getControl());
	Assert::same('<input type="file" name="file" id="frm-file">', (string) $input->getControl());
});


test(function () { // multiple
	$form = new Form;
	$input = $form->addUpload('file', 'Label', true);

	Assert::same('<input type="file" name="file[]" multiple id="frm-file">', (string) $input->getControl());
});


test(function () { // Html with translator
	$form = new Form;
	$input = $form->addUpload('file', 'Label');
	$input->setTranslator(new Translator);

	Assert::same('<label for="frm-file">Label</label>', (string) $input->getLabel());
	Assert::same('<label for="frm-file">Another label</label>', (string) $input->getLabel('Another label'));
	Assert::same('<label for="frm-file"><b>Another label</b></label>', (string) $input->getLabel(Html::el('b', 'Another label')));
});


test(function () { // validation rules
	$form = new Form;
	$input = $form->addUpload('file')->setRequired('required');

	Assert::same('<input type="file" name="file" id="frm-file" required data-nette-rules=\'[{"op":":filled","msg":"required"}]\'>', (string) $input->getControl());
});


test(function () { // container
	$form = new Form;
	$container = $form->addContainer('container');
	$input = $container->addUpload('file');

	Assert::same('<input type="file" name="container[file]" id="frm-container-file">', (string) $input->getControl());
});


test(function () { // rendering options
	$form = new Form;
	$input = $form->addUpload('file');

	Assert::same('file', $input->getOption('type'));

	Assert::null($input->getOption('rendered'));
	$input->getControl();
	Assert::true($input->getOption('rendered'));
});
