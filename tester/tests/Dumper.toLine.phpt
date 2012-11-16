<?php

use Tester\Assert,
	Tester\Dumper;

require __DIR__ . '/bootstrap.php';


Assert::match( 'NULL', Dumper::toLine(NULL) );
Assert::match( 'TRUE', Dumper::toLine(TRUE) );
Assert::match( 'FALSE', Dumper::toLine(FALSE) );
Assert::match( '0', Dumper::toLine(0) );
Assert::match( '1', Dumper::toLine(1) );
Assert::match( '0.0', Dumper::toLine(0.0) );
Assert::match( '0.1', Dumper::toLine(0.1) );
Assert::match( '""', Dumper::toLine('') );
Assert::match( '" "', Dumper::toLine(' ') );
Assert::match( '"0"', Dumper::toLine('0') );
Assert::match( '"\\x00"', Dumper::toLine("\x00") );
Assert::match( '"	"', Dumper::toLine("\t") );
Assert::match( '"\\xff"', Dumper::toLine("\xFF") );
Assert::match( "\"multi\nline\"", Dumper::toLine("multi\nline") );
Assert::match( '"Iñtërnâtiônàlizætiøn"', Dumper::toLine("I\xc3\xb1t\xc3\xabrn\xc3\xa2ti\xc3\xb4n\xc3\xa0liz\xc3\xa6ti\xc3\xb8n") );
Assert::match( 'array(5)', Dumper::toLine(array(1, 2, 3, 4, 'x')) );
Assert::match( "resource(stream)", Dumper::toLine(fopen(__FILE__, 'r')) );
Assert::match( 'stdClass(2)', Dumper::toLine((object) array(1, 2)) );
