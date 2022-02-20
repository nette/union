<?php

declare(strict_types=1);

use Nette\PhpGenerator\ClassType;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$class = ClassType::fromCode(file_get_contents(__DIR__ . '/fixtures/classes.php'));
Assert::type(ClassType::class, $class);
Assert::match(<<<'XX'
	/**
	 * Interface
	 * @author John Doe
	 */
	interface Interface1
	{
		function func1();
	}
	XX, (string) $class);


Assert::exception(function () {
	ClassType::fromCode('<?php');
}, Nette\InvalidStateException::class, 'The code does not contain any class.');
