<?php

/**
 * Test: Nette\Forms\Controls\MultiSelectBox.
 */

declare(strict_types=1);

use Nette\Forms\Form;
use Nette\Forms\Validator;
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


test(function () use ($series) { // Select with optgroups
	$_POST = ['multi' => ['red-dwarf']];

	$form = new Form;
	$input = $form->addMultiSelect('multi', null, [
		'usa' => [
			'the-simpsons' => 'The Simpsons',
			0 => 'South Park',
		],
		'uk' => [
			'red-dwarf' => 'Red Dwarf',
		],
	]);

	Assert::true($form->isValid());
	Assert::same(['red-dwarf'], $input->getValue());
	Assert::same(['red-dwarf' => 'Red Dwarf'], $input->getSelectedItems());
	Assert::true($input->isFilled());
});


test(function () use ($series) { // invalid input
	$_POST = ['select' => 'red-dwarf'];

	$form = new Form;
	$input = $form->addMultiSelect('select', null, $series);

	Assert::true($form->isValid());
	Assert::same([], $input->getValue());
	Assert::same([], $input->getSelectedItems());
	Assert::false($input->isFilled());
});


test(function () use ($series) { // multiple selected items, zero item
	$_POST = ['multi' => ['red-dwarf', 'unknown', 0]];

	$form = new Form;
	$input = $form->addMultiSelect('multi', null, $series);

	Assert::true($form->isValid());
	Assert::same(['red-dwarf', 0], $input->getValue());
	Assert::same(['red-dwarf', 'unknown', 0], $input->getRawValue());
	Assert::same(['red-dwarf' => 'Red Dwarf', 0 => 'South Park'], $input->getSelectedItems());
	Assert::true($input->isFilled());
});


test(function () use ($series) { // empty key
	$_POST = ['empty' => ['']];

	$form = new Form;
	$input = $form->addMultiSelect('empty', null, $series);

	Assert::true($form->isValid());
	Assert::same([''], $input->getValue());
	Assert::same(['' => 'Family Guy'], $input->getSelectedItems());
	Assert::true($input->isFilled());
});


test(function () use ($series) { // missing key
	$form = new Form;
	$input = $form->addMultiSelect('missing', null, $series);

	Assert::true($form->isValid());
	Assert::same([], $input->getValue());
	Assert::same([], $input->getSelectedItems());
	Assert::false($input->isFilled());
});


test(function () use ($series) { // disabled key
	$_POST = ['disabled' => 'red-dwarf'];

	$form = new Form;
	$input = $form->addMultiSelect('disabled', null, $series)
		->setDisabled();

	Assert::true($form->isValid());
	Assert::same([], $input->getValue());
});


test(function () use ($series) { // malformed data
	$_POST = ['malformed' => [[null]]];

	$form = new Form;
	$input = $form->addMultiSelect('malformed', null, $series);

	Assert::true($form->isValid());
	Assert::same([], $input->getValue());
	Assert::same([], $input->getSelectedItems());
	Assert::false($input->isFilled());
});


test(function () use ($series) { // validateLength
	$_POST = ['multi' => ['red-dwarf', 'unknown', 0]];

	$form = new Form;
	$input = $form->addMultiSelect('multi', null, $series);

	Assert::true(Validator::validateLength($input, 2));
	Assert::false(Validator::validateLength($input, 3));
	Assert::false(Validator::validateLength($input, [3]));
	Assert::true(Validator::validateLength($input, [0, 3]));
});


test(function () use ($series) { // validateEqual
	$_POST = ['multi' => ['red-dwarf', 'unknown', 0]];

	$form = new Form;
	$input = $form->addMultiSelect('multi', null, $series);

	Assert::true(Validator::validateEqual($input, ['red-dwarf', 0]));
	Assert::false(Validator::validateEqual($input, 'unknown'));
	Assert::false(Validator::validateEqual($input, ['unknown']));
	Assert::false(Validator::validateEqual($input, [0]));
});


test(function () use ($series) { // setItems without keys
	$_POST = ['multi' => ['red-dwarf']];

	$form = new Form;
	$input = $form->addMultiSelect('multi')->setItems(array_keys($series), false);
	Assert::same([
		'red-dwarf' => 'red-dwarf',
		'the-simpsons' => 'the-simpsons',
		0 => 0,
		'' => '',
	], $input->getItems());

	Assert::true($form->isValid());
	Assert::same(['red-dwarf'], $input->getValue());
	Assert::same(['red-dwarf' => 'red-dwarf'], $input->getSelectedItems());
	Assert::true($input->isFilled());
});


test(function () use ($series) { // setItems without keys
	$form = new Form;
	$input = $form->addMultiSelect('select')->setItems(range(1, 5), false);
	Assert::same([1 => 1, 2, 3, 4, 5], $input->getItems());
});


test(function () { // setItems without keys with optgroups
	$_POST = ['multi' => ['red-dwarf']];

	$form = new Form;
	$input = $form->addMultiSelect('multi')->setItems([
		'usa' => ['the-simpsons', 0],
		'uk' => ['red-dwarf'],
	], false);

	Assert::true($form->isValid());
	Assert::same(['red-dwarf'], $input->getValue());
	Assert::same(['red-dwarf' => 'red-dwarf'], $input->getSelectedItems());
	Assert::true($input->isFilled());
});


test(function () use ($series) { // setValue() and invalid argument
	$form = new Form;
	$input = $form->addMultiSelect('select', null, $series);
	$input->setValue(null);

	Assert::exception(function () use ($input) {
		$input->setValue('unknown');
	}, Nette\InvalidArgumentException::class, "Value 'unknown' are out of allowed set ['red-dwarf', 'the-simpsons', 0, ''] in field 'select'.");
});


test(function () { // object as value
	$form = new Form;
	$input = $form->addMultiSelect('select', null, ['2013-07-05 00:00:00' => 1])
		->setValue([new DateTime('2013-07-05')]);

	Assert::same(['2013-07-05 00:00:00'], $input->getValue());
});


test(function () { // object as item
	$form = new Form;
	$input = $form->addMultiSelect('select')
		->setItems([
			'group' => [new DateTime('2013-07-05')],
			new DateTime('2013-07-06'),
		], false)
		->setValue('2013-07-05 00:00:00');

	Assert::equal(['2013-07-05 00:00:00' => new DateTime('2013-07-05')], $input->getSelectedItems());
});


test(function () use ($series) { // disabled one
	$_POST = ['select' => ['red-dwarf', 0]];

	$form = new Form;
	$input = $form->addMultiSelect('select', null, $series)
		->setDisabled(['red-dwarf']);

	Assert::same([0], $input->getValue());

	unset($form['select']);
	$input = new Nette\Forms\Controls\MultiSelectBox(null, $series);
	$input->setDisabled(['red-dwarf']);
	$form['select'] = $input;

	Assert::same([0], $input->getValue());
});
