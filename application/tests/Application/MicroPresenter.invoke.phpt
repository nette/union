<?php

/**
 * Test: NetteModule\MicroPresenter
 */

use Nette\Application\Request;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class Invokable
{
	public function __invoke($page, $id, NetteModule\MicroPresenter $presenter)
	{
		Notes::add('Callback id ' . $id . ' page ' . $page);
	}
}


test(function () {
	$presenter = $p = new NetteModule\MicroPresenter;

	$presenter->run(new Request('Nette:Micro', 'GET', [
		'callback' => function ($id, $page, $presenter) use ($p) {
			Assert::same($p, $presenter);
			Notes::add('Callback id ' . $id . ' page ' . $page);
		},
		'id' => 1,
		'page' => 2,
	]));
	Assert::same([
		'Callback id 1 page 2',
	], Notes::fetch());
});


test(function () {
	$presenter = new NetteModule\MicroPresenter;

	$presenter->run(new Request('Nette:Micro', 'GET', [
		'callback' => new Invokable(),
		'id' => 1,
		'page' => 2,
	]));
	Assert::same([
		'Callback id 1 page 2',
	], Notes::fetch());
});



class MockContainer extends Nette\DI\Container
{
	function getByType($class, $need = TRUE)
	{
		Notes::add("getByType($class)");
		return new stdClass;
	}
}

test(function () {
	$presenter = new NetteModule\MicroPresenter(new MockContainer);

	$presenter->run(new Request('Nette:Micro', 'GET', [
		'callback' => function (stdClass $obj) {
			Notes::add(get_class($obj));
		},
	]));
	Assert::same([
		'getByType(stdClass)',
		'stdClass',
	], Notes::fetch());
});
