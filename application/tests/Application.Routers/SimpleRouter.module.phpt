<?php

/**
 * Test: Nette\Application\SimpleRouter and modules.
 *
 * @author     David Grudl
 * @package    Nette\Application
 * @subpackage UnitTests
 */

use Nette\Application\SimpleRouter;



require __DIR__ . '/../bootstrap.php';



$router = new SimpleRouter(array(
	'module' => 'main:sub',
));

$uri = new Nette\Web\UriScript('http://nette.org/file.php');
$uri->setScriptPath('/file.php');
$uri->setQuery(array(
	'presenter' => 'myPresenter',
));
$httpRequest = new Nette\Web\HttpRequest($uri);

$req = $router->match($httpRequest);
Assert::same( 'main:sub:myPresenter',  $req->getPresenterName() );

$url = $router->constructUrl($req, $httpRequest->uri);
Assert::same( 'http://nette.org/file.php?presenter=myPresenter',  $url );

$req = new Nette\Application\PresenterRequest(
	'othermodule:presenter',
	Nette\Web\HttpRequest::GET,
	array()
);
$url = $router->constructUrl($req, $httpRequest->uri);
Assert::null( $url );
