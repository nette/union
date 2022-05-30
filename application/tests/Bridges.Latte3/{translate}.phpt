<?php

/** @phpVersion 8.0 */

declare(strict_types=1);

use Nette\Localization\Translator;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (version_compare(Latte\Engine::VERSION, '3', '<')) {
	Tester\Environment::skip('Test for Latte 3');
}


$latte = new Latte\Engine;
$latte->setLoader(new Latte\Loaders\StringLoader);
$latte->addExtension(new Nette\Bridges\ApplicationLatte\TranslatorExtension(null));

Assert::match(
	<<<'XX'
		%A%
				$ʟ_fi = new LR\FilterInfo('html');
				echo LR\Filters::convertTo($ʟ_fi, 'html', $this->filters->filterContent('translate', $ʟ_fi, 'abc')) /* line 1 */;
		%A%
		XX,
	$latte->compile('{translate}abc{/translate}'),
);

Assert::contains(
	'echo LR\Filters::convertTo($ʟ_fi, \'html\', $this->filters->filterContent(\'translate\', $ʟ_fi, \'abc\', 10, 20)) /* line 1 */;',
	$latte->compile('{translate 10, 20}abc{/translate}'),
);

Assert::match(
	<<<'XX'
		%A%
				$ʟ_fi = new LR\FilterInfo('html');
				echo LR\Filters::convertTo($ʟ_fi, 'html', $this->filters->filterContent('filter', $ʟ_fi, $this->filters->filterContent('translate', $ʟ_fi, 'abc'))) /* line 1 */;
		%A%
		XX,
	$latte->compile('{translate|filter}abc{/translate}'),
);

Assert::match(
	<<<'XX'
		%A%
				ob_start(fn() => '');
				try {
					if (true) /* line 1 */ {
						echo 'abc';
					}

				} finally {
					$ʟ_tmp = ob_get_clean();
				}
				$ʟ_fi = new LR\FilterInfo('html');
				echo LR\Filters::convertTo($ʟ_fi, 'html', $this->filters->filterContent('translate', $ʟ_fi, $ʟ_tmp)) /* line 1 */;
		%A%
		XX,
	$latte->compile('{translate}{if true}abc{/if}{/translate}'),
);

Assert::notContains(
	"'translate'",
	$latte->compile('{translate /}'),
);


class MyTranslator implements Translator
{
	public function translate($message, ...$parameters): string
	{
		return strrev($message) . implode(',', $parameters);
	}
}

$latte = new Latte\Engine;
$latte->setLoader(new Latte\Loaders\StringLoader);
$latte->addExtension(new Nette\Bridges\ApplicationLatte\TranslatorExtension(new MyTranslator));
Assert::contains(
	'echo LR\Filters::convertTo($ʟ_fi, \'html\', $this->filters->filterContent(\'translate\', $ʟ_fi, \'a&b\', 1, 2))',
	$latte->compile('{translate 1,2}a&b{/translate}'),
);
Assert::same(
	'b&a1,2',
	$latte->renderToString('{translate 1,2}a&b{/translate}'),
);


$latte->addExtension(new Nette\Bridges\ApplicationLatte\TranslatorExtension(new MyTranslator, 'en'));
Assert::contains(
	'echo LR\Filters::convertTo($ʟ_fi, \'html\', \'b&a1,2\')',
	$latte->compile('{translate 1,2}a&b{/translate}'),
);
Assert::same(
	'b&a1,2',
	$latte->renderToString('{translate 1,2}a&b{/translate}'),
);