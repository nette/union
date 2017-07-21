<?php

/**
 * Test: Nette\Forms\Controls\RadioList.
 */

declare(strict_types=1);

use Nette\Forms\Form;
use Nette\Utils\DateTime;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


before(function () {
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_POST = $_FILES = [];
});


$series = [
	'red-dwarf' => 'Red Dwarf',
	'the-simpsons' => 'The Simpsons',
	0 => 'South Park',
	'' => 'Family Guy',
];


test(function () use ($series) { // Radio list
	$_POST = ['radio' => 'red-dwarf'];

	$form = new Form;
	$input = $form->addRadioList('radio', null, $series);

	Assert::true($form->isValid());
	Assert::same('red-dwarf', $input->getValue());
	Assert::same('Red Dwarf', $input->getSelectedItem());
	Assert::true($input->isFilled());
});


test(function () use ($series) { // Radio list with invalid input
	$_POST = ['radio' => 'days-of-our-lives'];

	$form = new Form;
	$input = $form->addRadioList('radio', null, $series);

	Assert::true($form->isValid());
	Assert::null($input->getValue());
	Assert::null($input->getSelectedItem());
	Assert::false($input->isFilled());
});


test(function () use ($series) { // Indexed arrays
	$_POST = ['zero' => 0];

	$form = new Form;
	$input = $form->addRadioList('zero', null, $series);

	Assert::true($form->isValid());
	Assert::same(0, $input->getValue());
	Assert::same(0, $input->getRawValue());
	Assert::same('South Park', $input->getSelectedItem());
	Assert::true($input->isFilled());
});


test(function () use ($series) { // empty key
	$_POST = ['empty' => ''];

	$form = new Form;
	$input = $form->addRadioList('empty', null, $series);

	Assert::true($form->isValid());
	Assert::same('', $input->getValue());
	Assert::same('Family Guy', $input->getSelectedItem());
	Assert::true($input->isFilled());
});


test(function () use ($series) { // missing key
	$form = new Form;
	$input = $form->addRadioList('missing', null, $series);

	Assert::true($form->isValid());
	Assert::null($input->getValue());
	Assert::null($input->getSelectedItem());
	Assert::false($input->isFilled());
});


test(function () use ($series) { // disabled key
	$_POST = ['disabled' => 'red-dwarf'];

	$form = new Form;
	$input = $form->addRadioList('disabled', null, $series)
		->setDisabled();

	Assert::true($form->isValid());
	Assert::null($input->getValue());
	Assert::false($input->isFilled());
});


test(function () use ($series) { // malformed data
	$_POST = ['malformed' => [null]];

	$form = new Form;
	$input = $form->addRadioList('malformed', null, $series);

	Assert::true($form->isValid());
	Assert::null($input->getValue());
	Assert::null($input->getSelectedItem());
	Assert::false($input->isFilled());
});


test(function () use ($series) { // setItems without keys
	$_POST = ['select' => 'red-dwarf'];

	$form = new Form;
	$input = $form->addRadioList('select')->setItems(array_keys($series), false);

	Assert::true($form->isValid());
	Assert::same('red-dwarf', $input->getValue());
	Assert::same('red-dwarf', $input->getSelectedItem());
	Assert::true($input->isFilled());
});


test(function () use ($series) { // setValue() and invalid argument
	$form = new Form;
	$input = $form->addRadioList('radio', null, $series);
	$input->setValue(null);

	Assert::exception(function () use ($input) {
		$input->setValue('unknown');
	}, Nette\InvalidArgumentException::class, "Value 'unknown' is out of allowed set ['red-dwarf', 'the-simpsons', 0, ''] in field 'radio'.");
});


test(function () { // object as value
	$form = new Form;
	$input = $form->addRadioList('radio', null, ['2013-07-05 00:00:00' => 1])
		->setValue(new DateTime('2013-07-05'));

	Assert::same('2013-07-05 00:00:00', $input->getValue());
});


test(function () { // object as item
	$form = new Form;
	$input = $form->addRadioList('radio')
		->setItems([new DateTime('2013-07-05')], false)
		->setValue(new DateTime('2013-07-05'));

	Assert::same('2013-07-05 00:00:00', $input->getValue());
});


test(function () use ($series) { // disabled one
	$_POST = ['radio' => 'red-dwarf'];

	$form = new Form;
	$input = $form->addRadioList('radio', null, $series)
		->setDisabled(['red-dwarf']);

	Assert::null($input->getValue());

	unset($form['radio']);
	$input = new Nette\Forms\Controls\RadioList(null, $series);
	$input->setDisabled(['red-dwarf']);
	$form['radio'] = $input;

	Assert::null($input->getValue());
});
