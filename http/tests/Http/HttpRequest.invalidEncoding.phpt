<?php

/**
 * Test: Nette\Web\HttpRequest invalid encoding.
 *
 * @author     David Grudl
 * @package    Nette\Web
 * @subpackage UnitTests
 */

use Nette\Web\HttpRequest,
	Nette\Web\HttpUploadedFile;



require __DIR__ . '/../initialize.php';



// Setup environment
define('INVALID', "\x76\xC4\xC5\xBE");
define('CONTROL_CHARACTERS', "A\x00B\x80C");

$_GET = array(
	'invalid' => INVALID,
	'control' => CONTROL_CHARACTERS,
	INVALID => '1',
	CONTROL_CHARACTERS => '1',
	'array' => array(INVALID => '1'),
);

$_POST = array(
	'invalid' => INVALID,
	'control' => CONTROL_CHARACTERS,
	INVALID => '1',
	CONTROL_CHARACTERS => '1',
	'array' => array(INVALID => '1'),
);

$_COOKIE = array(
	'invalid' => INVALID,
	'control' => CONTROL_CHARACTERS,
	INVALID => '1',
	CONTROL_CHARACTERS => '1',
	'array' => array(INVALID => '1'),
);

$_FILES = array(
	INVALID => array(
		'name' => 'readme.txt',
		'type' => 'text/plain',
		'tmp_name' => 'C:\\PHP\\temp\\php1D5B.tmp',
		'error' => 0,
		'size' => 209,
	),
	CONTROL_CHARACTERS => array(
		'name' => 'readme.txt',
		'type' => 'text/plain',
		'tmp_name' => 'C:\\PHP\\temp\\php1D5B.tmp',
		'error' => 0,
		'size' => 209,
	),
	'file1' => array(
		'name' => INVALID,
		'type' => 'text/plain',
		'tmp_name' => 'C:\\PHP\\temp\\php1D5B.tmp',
		'error' => 0,
		'size' => 209,
	),
);

// unfiltered data
$request = new HttpRequest;

Assert::true( $request->getQuery('invalid') === INVALID );
Assert::true( $request->getQuery('control') === CONTROL_CHARACTERS );
Assert::same( '1', $request->getQuery(INVALID) );
Assert::same( '1', $request->getQuery(CONTROL_CHARACTERS) );
Assert::same( '1', $request->query['array'][INVALID] );

Assert::true( $request->getPost('invalid') === INVALID );
Assert::true( $request->getPost('control') === CONTROL_CHARACTERS );
Assert::same( '1', $request->getPost(INVALID) );
Assert::same( '1', $request->getPost(CONTROL_CHARACTERS) );
Assert::same( '1', $request->post['array'][INVALID] );

Assert::true( $request->getCookie('invalid') === INVALID );
Assert::true( $request->getCookie('control') === CONTROL_CHARACTERS );
Assert::same( '1', $request->getCookie(INVALID) );
Assert::same( '1', $request->getCookie(CONTROL_CHARACTERS) );
Assert::same( '1', $request->cookies['array'][INVALID] );

Assert::true( $request->getFile(INVALID) instanceof HttpUploadedFile );
Assert::true( $request->getFile(CONTROL_CHARACTERS) instanceof HttpUploadedFile );
Assert::true( $request->files['file1'] instanceof HttpUploadedFile );


// filtered data
$request->setEncoding('UTF-8');

Assert::same( "v\xc5\xbe", $request->getQuery('invalid') );
Assert::same( 'ABC', $request->getQuery('control') );
Assert::null( $request->getQuery(INVALID) );
Assert::null( $request->getQuery(CONTROL_CHARACTERS) );
Assert::false( isset($request->query['array'][INVALID]) );

Assert::same( "v\xc5\xbe", $request->getPost('invalid') );
Assert::same( 'ABC', $request->getPost('control') );
Assert::null( $request->getPost(INVALID) );
Assert::null( $request->getPost(CONTROL_CHARACTERS) );
Assert::false( isset($request->post['array'][INVALID]) );

Assert::same( "v\xc5\xbe", $request->getCookie('invalid') );
Assert::same( 'ABC', $request->getCookie('control') );
Assert::null( $request->getCookie(INVALID) );
Assert::null( $request->getCookie(CONTROL_CHARACTERS) );
Assert::false( isset($request->cookies['array'][INVALID]) );

Assert::null( $request->getFile(INVALID) );
Assert::null( $request->getFile(CONTROL_CHARACTERS) );
Assert::true( $request->files['file1'] instanceof HttpUploadedFile );
Assert::same( "v\xc5\xbe", $request->files['file1']->name );
