<?php
declare(strict_types=1);

use Nette\PhpGenerator\Closure;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$function = new Closure;
$function
	->setReturnReference(true)
	->setBody('return $a + $b;');

$function->addParameter('a');
$function->addParameter('b');
$function->addUse('this');
$function->addUse('vars')
	->setReference(true);

Assert::match(
'function &($a, $b) use ($this, &$vars) {
	return $a + $b;
}', (string) $function);


$uses = $function->getUses();
Assert::count(2, $uses);
Assert::type(Nette\PhpGenerator\Parameter::class, $uses[0]);
Assert::type(Nette\PhpGenerator\Parameter::class, $uses[1]);

$uses = $function->setUses([$uses[0]]);

Assert::match(
'function &($a, $b) use ($this) {
	return $a + $b;
}', (string) $function);

Assert::exception(function () {
	$function = new Closure;
	$function->setUses([123]);
}, TypeError::class);


$closure = function (stdClass $a, $b = null) {};
$function = Closure::from($closure);
Assert::match(
'function (stdClass $a, $b = null) {
}', (string) $function);
