<?php

/**
 * Test: Nette\Forms\Controls\CsrfProtection and BREACH attack.
 */

declare(strict_types=1);

use Nette\Forms\Form;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';



$form = new Form;
$input = $form->addProtection('Security token did not match. Possible CSRF attack.');
$token = $input->getControl()->value;

Assert::notSame($token, $input->getControl()->value);

/* BREACH attack test: skipped because $input->getControl() always generates different token
$charlist = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
$charCount = strlen($charlist);

$strings = [];
for ($a = 0; $a < $charCount; $a++) {
	for ($b = 0; $b < $charCount; $b++) {
		$strings[] = $charlist[$a] . $charlist[$b];
	}
}

for ($i = 3; $i <= strlen($token); $i++) {
	$code = (string) $input->getControl();
	$shortest = null;
	$adepts = [];
	foreach ($strings as $string) {
		for ($j = 0; $j < $charCount; $j++) {
			$try = $string . $charlist[$j];
			$length = strlen(gzdeflate($code . $try));
			if ($shortest === null || $length < $shortest) {
				$shortest = $length;
				$adepts = [];
			}
			if ($shortest === $length) {
				$adepts[] = $try;
			}
		}
	}
	$strings = $adepts;
}

foreach ($strings as $string) {
	$input->setValue($string);
	Assert::false(CsrfProtection::validateCsrf($input));
}
*/
