<?php

/**
 * Test: Nette\SafeStream basic usage.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\SafeStream;



require __DIR__ . '/../NetteTest/initialize.php';



SafeStream::register();


// actually it creates temporary file
$handle = fopen('safe://myfile.txt', 'x');
fwrite($handle, 'atomic and safe');
// and now rename it
fclose($handle);

// removes file thread-safe way
unlink('safe://myfile.txt');

// this is not thread safe - don't relay on returned value
$ok = is_file('safe://SafeStream.php');



__halt_compiler() ?>

------EXPECT------
