<?php

/**
 * Test: renderSnippets and control wrapped in a snippet
 * @phpVersion 8.0
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (version_compare(Latte\Engine::VERSION, '3', '<')) {
	Tester\Environment::skip('Test for Latte 3');
}


class TestControl extends Nette\Application\UI\Control
{
	public int $counter = 0;


	public function render(int $arg = 0)
	{
		$this->counter++;
		$latte = new Latte\Engine;
		$latte->setLoader(new Latte\Loaders\StringLoader);
		$latte->addExtension(new Nette\Bridges\ApplicationLatte\UIExtension($this));
		$latte->render('{snippet foo}hello {$arg}{/snippet}', ['arg' => $arg]);
	}
}

class TestPresenter extends Nette\Application\UI\Presenter
{
	public function createComponentTest()
	{
		return new TestControl;
	}


	public function render()
	{
		$latte = new Latte\Engine;
		$latte->setLoader(new Latte\Loaders\StringLoader);
		$latte->addExtension(new Nette\Bridges\ApplicationLatte\UIExtension($this));
		$latte->render('{snippetArea foo}{control test 123}{/snippetArea}');
	}
}


$presenter = new TestPresenter;
$presenter->injectPrimary(null, null, null, new Http\Request(new Http\UrlScript('/')), new Http\Response);
$presenter->snippetMode = true;
$presenter->redrawControl('foo');
$presenter['test']->redrawControl('foo');
$presenter->render();
Assert::same([
	'snippets' => [
		'snippet-test-foo' => 'hello 123',
	],
], (array) $presenter->payload);
Assert::same(1, $presenter['test']->counter);


$presenter = new TestPresenter;
$presenter->injectPrimary(null, null, null, new Http\Request(new Http\UrlScript('/')), new Http\Response);
$presenter->snippetMode = true;
$presenter->redrawControl('foo');
$presenter['test']->redrawControl();
$presenter->render();
Assert::same([
	'snippets' => [
		'snippet-test-foo' => 'hello 123',
	],
], (array) $presenter->payload);
Assert::same(2, $presenter['test']->counter);


$presenter = new TestPresenter;
$presenter->injectPrimary(null, null, null, new Http\Request(new Http\UrlScript('/')), new Http\Response);
$presenter->snippetMode = true;
$presenter['test']->redrawControl('foo');
$presenter->render();
Assert::same([
	'snippets' => [
		'snippet-test-foo' => 'hello 0',
	],
], (array) $presenter->payload);
Assert::same(1, $presenter['test']->counter);
