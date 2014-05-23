<?php

/**
 * Test: Nette\Utils\Strings::toAscii()
 */

use Nette\Utils\Strings,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Assert::same( 'ZLUTOUCKY KUN oooo--', Strings::toAscii("\xc5\xbdLU\xc5\xa4OU\xc4\x8cK\xc3\x9d K\xc5\xae\xc5\x87 \xc3\xb6\xc5\x91\xc3\xb4o\x2d\xe2\x80\x93") ); // ŽLUŤOUČKÝ KŮŇ öőôo
Assert::same( 'Zlutoucky kun', Strings::toAscii("Z\xCC\x8Clut\xCC\x8Couc\xCC\x8Cky\xCC\x81 ku\xCC\x8An\xCC\x8C") ); // Žluťoučký kůň with combining characters
Assert::same( 'Z `\'"^~', Strings::toAscii("\xc5\xbd `'\"^~") );
Assert::same( '"""\'\'\'>><<', Strings::toAscii("\xE2\x80\x9E\xE2\x80\x9C\xE2\x80\x9D\xE2\x80\x9A\xE2\x80\x98\xE2\x80\x99\xC2\xBB\xC2\xAB") ); // „“”‚‘’»«
