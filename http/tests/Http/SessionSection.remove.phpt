<?php

/**
 * Test: Nette\Http\SessionSection remove.
 *
 * @author     David Grudl
 */

use Nette\Http\Session,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript), new Nette\Http\Response);

$namespace = $session->getSection('three');
$namespace->a = 'apple';
$namespace->p = 'papaya';
$namespace['c'] = 'cherry';

$namespace = $session->getSection('three');
Assert::same( 'a=apple&p=papaya&c=cherry', http_build_query($namespace->getIterator()) );


// removing
$namespace->remove();
Assert::same( '', http_build_query($namespace->getIterator()) );
