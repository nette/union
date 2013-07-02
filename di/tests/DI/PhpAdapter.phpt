<?php

/**
 * Test: Nette\DI\Config\Adapters\PhpAdapter
 *
 * @author     David Grudl
 * @package    Nette\DI\Config
 */

use Nette\DI\Config;


require __DIR__ . '/../bootstrap.php';

define('TEMP_FILE', TEMP_DIR . '/cfg.php');


// Load INI
$config = new Config\Loader;
$data = $config->load('files/phpAdapter.php');
Assert::same( array(
	'webname' => 'the example',
	'database' => array(
		'adapter' => 'pdo_mysql',
		'params' => array(
			'host' => 'db.example.com',
			'username' => 'dbuser',
			'password' => 'secret',
			'dbname' => 'dbname',
		),
	),
), $data );


$config->save($data, TEMP_FILE);
Assert::match( <<<EOD
<?php // generated by Nette
return array(
	'webname' => 'the example',
	'database' => array(
		'adapter' => 'pdo_mysql',
		'params' => array(
			'host' => 'db.example.com',
			'username' => 'dbuser',
			'password' => 'secret',
			'dbname' => 'dbname',
		),
	),
);
EOD
, file_get_contents(TEMP_FILE) );
