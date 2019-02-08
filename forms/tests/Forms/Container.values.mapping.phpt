<?php

declare(strict_types=1);

use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class FormData
{
	/** @var string */
	public $title;

	/** @var FormFirstLevel */
	public $first;
}


class FormFirstLevel
{
	/** @var string */
	public $name;

	/** @var int */
	public $age;

	/** @var FormSecondLevel */
	public $second;
}


class FormSecondLevel
{
	/** @var string */
	public $city;
}


function hydrate(string $class, array $data)
{
	$obj = new $class;
	foreach ($data as $key => $value) {
		$obj->$key = $value;
	}
	return $obj;
}


$_POST = [
	'title' => 'sent title',
	'first' => [
		'age' => '999',
		'second' => [
			'city' => 'sent city',
		],
	],
];


function createForm(): Form
{
	$form = new Form;
	$form->addText('title');

	$first = $form->addContainer('first');
	$first->addText('name');
	$first->addInteger('age');

	$second = $first->addContainer('second');
	$second->addText('city');
	return $form;
}


test(function () { // setDefaults() + object
	$form = createForm();
	Assert::false($form->isSubmitted());

	$form->setDefaults(hydrate(FormData::class, [
		'title' => 'xxx',
		'extra' => '50',
		'first' => hydrate(FormFirstLevel::class, [
			'name' => 'yyy',
			'age' => '30',
			'second' => hydrate(FormSecondLevel::class, [
				'city' => 'zzz',
			]),
		]),
	]));

	Assert::same([
		'title' => 'xxx',
		'first' => [
			'name' => 'yyy',
			'age' => '30',
			'second' => [
				'city' => 'zzz',
			],
		],
	], $form->getValues(true));
});


test(function () { // submitted form + getValues()
	$_SERVER['REQUEST_METHOD'] = 'POST';

	$form = createForm();
	Assert::truthy($form->isSubmitted());
	Assert::equal(ArrayHash::from([
		'title' => 'sent title',
		'first' => ArrayHash::from([
			'name' => '',
			'age' => '999',
			'second' => ArrayHash::from([
				'city' => 'sent city',
			]),
		]),
	]), $form->getValues());
});


test(function () { // submitted form + reset()
	$_SERVER['REQUEST_METHOD'] = 'POST';

	$form = createForm();
	Assert::truthy($form->isSubmitted());

	$form->reset();

	Assert::false($form->isSubmitted());
	Assert::equal(ArrayHash::from([
		'title' => '',
		'first' => ArrayHash::from([
			'name' => '',
			'age' => null,
			'second' => ArrayHash::from([
				'city' => '',
			]),
		]),
	]), $form->getValues());
});


test(function () { // setValues() + object
	$_SERVER['REQUEST_METHOD'] = 'POST';

	$form = createForm();
	Assert::truthy($form->isSubmitted());

	$form->setValues(hydrate(FormData::class, [
		'title' => 'new1',
		'first' => hydrate(FormFirstLevel::class, [
			'name' => 'new2',
			// age => null
		]),
	]));

	Assert::equal(ArrayHash::from([
		'title' => 'new1',
		'first' => ArrayHash::from([
			'name' => 'new2',
			'age' => null,
			'second' => ArrayHash::from([
				'city' => 'sent city',
			]),
		]),
	]), $form->getValues());

	// erase
	$form->setValues(hydrate(FormData::class, [
		'title' => 'new1',
		'first' => hydrate(FormFirstLevel::class, [
			'name' => 'new2',
		]),
	]), true);

	Assert::equal(ArrayHash::from([
		'title' => 'new1',
		'first' => ArrayHash::from([
			'name' => 'new2',
			'age' => null,
			'second' => ArrayHash::from([
				'city' => '',
			]),
		]),
	]), $form->getValues());
});


test(function () { // onSuccess test
	$_SERVER['REQUEST_METHOD'] = 'POST';

	$form = createForm();
	$form->onSuccess[] = function (Form $form, array $values) {
		Assert::same([
			'title' => 'sent title',
			'first' => [
				'name' => '',
				'age' => 999,
				'second' => [
					'city' => 'sent city',
				],
			],
		], $values);
	};

	$form->onSuccess[] = function (Form $form, ArrayHash $values) {
		Assert::equal(ArrayHash::from([
			'title' => 'sent title',
			'first' => ArrayHash::from([
				'name' => '',
				'age' => 999,
				'second' => ArrayHash::from([
					'city' => 'sent city',
				]),
			]),
		]), $values);
	};

	$form->onSuccess[] = function (Form $form, $values) {
		Assert::equal(ArrayHash::from([
			'title' => 'sent title',
			'first' => ArrayHash::from([
				'name' => '',
				'age' => 999,
				'second' => ArrayHash::from([
					'city' => 'sent city',
				]),
			]),
		]), $values);
	};

	$ok = false;
	$form->onSuccess[] = function () use (&$ok) {
		$ok = true;
	};

	$form->fireEvents();
	Assert::true($ok);
});
