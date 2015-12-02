<?php

/**
 * Test: Nette\Mail\Message - textual and HTML body with attachment.
 */

use Nette\Mail\Message;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/Mail.php';


$mail = new Message();

$mail->setFrom('John Doe <doe@example.com>');
$mail->addTo('Lady Jane <jane@example.com>');
$mail->setSubject('Hello Jane!');

$mail->setBody('Sample text');

$mail->setHTMLBody('<b>Sample text</b>');

$mail->addAttachment(__DIR__ . '/files/example.zip', NULL, 'application/zip');

$mailer = new TestMailer();
$mailer->send($mail);

Assert::matchFile(__DIR__ . '/Mail.textualAndHtmlBody.attachment.expect', TestMailer::$output);
