<?php

/**
 * Test: Nette\Mail\Message invalid headers.
 */

use Nette\Mail\Message;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Mail.php';


$mail = new Message();

Assert::exception(function () use ($mail) {
	$mail->setHeader('', 'value');
}, InvalidArgumentException::class, "Header name must be non-empty alphanumeric string, '' given.");

Assert::exception(function () use ($mail) {
	$mail->setHeader(' name', 'value');
}, InvalidArgumentException::class, "Header name must be non-empty alphanumeric string, ' name' given.");

Assert::exception(function () use ($mail) {
	$mail->setHeader('n*ame', 'value');
}, InvalidArgumentException::class, "Header name must be non-empty alphanumeric string, 'n*ame' given.");
