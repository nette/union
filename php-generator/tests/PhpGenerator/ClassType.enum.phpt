<?php

/**
 * Test: Nette\PhpGenerator for enum.
 */

declare(strict_types=1);

use Nette\PhpGenerator\EnumType;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$enum = new EnumType('Suit');

Assert::true($enum->isEnum());

$enum->addComment("Description of class.\nThis is example\n")
	->addAttribute('ExampleAttribute');

$enum->addConstant('ACTIVE', false);
$enum->addTrait('ObjectTrait');

$enum->addMethod('foo')
	->setBody('return 10;');

$enum->addCase('Clubs')
	->addComment('♣')
	->addAttribute('ValueAttribute');
$enum->addCase('Diamonds')
	->addComment('♦');
$enum->addCase('Hearts');
$enum->addCase('Spades');

$res[] = $enum;


$enum = new EnumType('Method');
$enum->addImplement('IOne');

$enum->addCase('GET', 'get');
$enum->addCase('POST', 'post');

$res[] = $enum;

sameFile(__DIR__ . '/expected/ClassType.enum.expect', implode("\n", $res));
