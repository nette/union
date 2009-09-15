<?php

/**
 * Test: SimpleRouter with secured connection.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette\Application
 * @subpackage UnitTests
 */

/*use Nette\Application\SimpleRouter;*/



require dirname(__FILE__) . '/../NetteTest/initialize.php';

require dirname(__FILE__) . '/SimpleRouter.inc';



$router = new SimpleRouter(array(
	'id' => 12,
	'any' => 'anyvalue',
), SimpleRouter::SECURED);

$httpRequest = new MockHttpRequest;
$httpRequest->setQuery(array(
	'presenter' => 'myPresenter',
));

$req = new /*Nette\Application\*/PresenterRequest(
	'othermodule:presenter',
	/*Nette\Web\*/HttpRequest::GET,
	array()
);

$url = $router->constructUrl($req, $httpRequest);
dump( $url ); // "https://nettephp.com/file.php?presenter=othermodule%3Apresenter"



__halt_compiler();

------EXPECT------
string(63) "https://nettephp.com/file.php?presenter=othermodule%3Apresenter"
