<?php

/**
 * Test: Nette\Utils\Json::encode()
 *
 * @author     David Grudl
 * @package    Nette\Utils
 * @subpackage UnitTests
 */

use Nette\Utils\Json;



require __DIR__ . '/../bootstrap.php';



Assert::same( '"ok"', Json::encode('ok') );




Assert::throws(function() {
	Json::encode(array("bad utf\xFF"));
}, 'Nette\Utils\JsonException', 'json_encode(): Invalid UTF-8 sequence in argument');



Assert::throws(function() {
	$arr = array('recursive');
	$arr[] = & $arr;
	Json::encode($arr);
}, 'Nette\Utils\JsonException', 'json_encode(): recursion detected');



if (PHP_VERSION_ID >= 50400) {
	// default JSON_UNESCAPED_UNICODE
	Assert::same( "\"I\xc3\xb1t\xc3\xabrn\xc3\xa2ti\xc3\xb4n\xc3\xa0liz\xc3\xa6ti\xc3\xb8n\"", Json::encode("I\xc3\xb1t\xc3\xabrn\xc3\xa2ti\xc3\xb4n\xc3\xa0liz\xc3\xa6ti\xc3\xb8n") );

	// JSON_PRETTY_PRINT
	Assert::same( "[\n    1,\n    2,\n    3\n]", Json::encode(array(1,2,3,), Json::PRETTY) );
}
