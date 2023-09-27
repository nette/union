<?php

/**
 * Test: Nette\Forms\Controls\DateTimeControl.
 */

declare(strict_types=1);

use Nette\Forms\Form;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


setUp(function () {
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_POST = $_FILES = [];
	$_COOKIE[Nette\Http\Helpers::StrictCookieName] = '1';
	Form::initialize(true);
});


test('not present', function () {
	$form = new Form;
	$input = $form->addDate('unknown');
	Assert::null($input->getValue());
	Assert::false($input->isFilled());
});


test('invalid data', function () {
	$_POST = ['malformed' => ['']];
	$form = new Form;
	$input = $form->addDate('malformed');
	Assert::null($input->getValue());
	Assert::false($input->isFilled());
});


test('invalid format', function () {
	$_POST = ['text' => 'invalid'];
	$form = new Form;
	$input = $form->addDate('date');
	Assert::null($input->getValue());
	Assert::false($input->isFilled());
});


test('invalid date', function () {
	$_POST = ['date' => '2023-13-22'];
	$form = new Form;
	$input = $form->addDate('date');
	Assert::null($input->getValue());
	Assert::false($input->isFilled());
});


test('invalid time', function () {
	$_POST = ['time' => '10:60'];
	$form = new Form;
	$input = $form->addTime('time');
	Assert::null($input->getValue());
	Assert::false($input->isFilled());
});


test('valid date', function () {
	$_POST = ['date' => '2023-10-22'];
	$form = new Form;
	$input = $form->addDate('date');
	Assert::equal(new DateTimeImmutable('2023-10-22 00:00'), $input->getValue());
	Assert::true($input->isFilled());
});


test('valid time', function () {
	$_POST = ['time' => '10:22:33.44'];
	$form = new Form;
	$input = $form->addTime('time');
	Assert::equal(new DateTimeImmutable('0000-01-01 10:22'), $input->getValue());
	Assert::true($input->isFilled());
});


test('valid time with seconds', function () {
	$_POST = ['time' => '10:22:33.44'];
	$form = new Form;
	$input = $form->addTime('time', withSeconds: true);
	Assert::equal(new DateTimeImmutable('0000-01-01 10:22:33'), $input->getValue());
	Assert::true($input->isFilled());
});


test('valid date-time', function () {
	$_POST = ['date' => '2023-10-22T10:23:11.123'];
	$form = new Form;
	$input = $form->addDateTime('date');
	Assert::equal(new DateTimeImmutable('2023-10-22 10:23:00'), $input->getValue());
	Assert::true($input->isFilled());
});


test('valid date-time with seconds', function () {
	$_POST = ['date' => '2023-10-22T10:23:11.123'];
	$form = new Form;
	$input = $form->addDateTime('date', withSeconds: true);
	Assert::equal(new DateTimeImmutable('2023-10-22 10:23:11'), $input->getValue());
	Assert::true($input->isFilled());
});


test('setValue() and invalid argument', function () {
	$form = new Form;
	$input = $form->addDate('date');
	$input->setValue(null);

	Assert::exception(function () use ($input) {
		$input->setValue([]);
	}, Nette\InvalidArgumentException::class, 'Value must be DateTimeInterface or string or null, array given.');
});


test('string as date', function () {
	$form = new Form;
	$input = $form->addDate('date')
		->setValue('2013-07-05 10:30');

	Assert::equal(new DateTimeImmutable('2013-07-05 00:00'), $input->getValue());
});


test('DateTime object as date', function () {
	$form = new Form;
	$input = $form->addDate('date')
		->setValue(new Nette\Utils\DateTime('2023-10-05 11:22:33.44'));

	Assert::equal(new DateTimeImmutable('2023-10-05 00:00'), $input->getValue());
});


test('DateTime object as time', function () {
	$form = new Form;
	$input = $form->addTime('time')
		->setValue(new Nette\Utils\DateTime('2023-10-05 11:22:33.44'));

	Assert::equal(new DateTimeImmutable('0000-01-01 11:22'), $input->getValue());
});


test('DateTime object as date-time', function () {
	$form = new Form;
	$input = $form->addDateTime('time')
		->setValue(new Nette\Utils\DateTime('2023-10-05 11:22:33.44'));

	Assert::equal(new DateTimeImmutable('2023-10-05 11:22'), $input->getValue());
});


test('DateTime object as date-time with seconds', function () {
	$form = new Form;
	$input = $form->addDateTime('time', withSeconds: true)
		->setValue(new Nette\Utils\DateTime('2023-10-05 11:22:33.44'));

	Assert::equal(new DateTimeImmutable('2023-10-05 11:22:33'), $input->getValue());
});
