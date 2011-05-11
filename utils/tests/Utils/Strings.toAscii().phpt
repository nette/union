<?php

/**
 * Test: Nette\Utils\Strings::toAscii()
 *
 * @author     David Grudl
 * @package    Nette\Utils
 * @subpackage UnitTests
 */

use Nette\Utils\Strings;



require __DIR__ . '/../bootstrap.php';



Assert::same( 'ZLUTOUCKY KUN oooo', Strings::toAscii("\xc5\xbdLU\xc5\xa4OU\xc4\x8cK\xc3\x9d K\xc5\xae\xc5\x87 \xc3\xb6\xc5\x91\xc3\xb4o") ); // ŽLUŤOUČKÝ KŮŇ öőôo
Assert::same( 'Z `\'"^~', Strings::toAscii("\xc5\xbd `'\"^~") );
