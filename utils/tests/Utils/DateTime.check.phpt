<?php

/**
 * Test: Nette\Utils\DateTime::check().
 */

declare(strict_types=1);

use Nette\Utils\DateTime;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


date_default_timezone_set('Europe/Prague');

Assert::exception(
	fn() => DateTime::check(1985, 2, 29),
	Nette\InvalidArgumentException::class,
	'The date 1985-02-29 is not valid.',
);

Assert::exception(
	fn() => DateTime::check(0, 12, 9),
	Nette\InvalidArgumentException::class,
	'The date 0000-12-09 is not valid.',
);

Assert::exception(
	fn() => DateTime::check(1985, 0, 9),
	Nette\InvalidArgumentException::class,
	'Month value (0) is out of range.',
);

Assert::exception(
	fn() => DateTime::check(1985, 13, 9),
	Nette\InvalidArgumentException::class,
	'Month value (13) is out of range.',
);

Assert::exception(
	fn() => DateTime::check(1985, 12, 0),
	Nette\InvalidArgumentException::class,
	'Day value (0) is out of range.',
);

Assert::exception(
	fn() => DateTime::check(1985, 12, 32),
	Nette\InvalidArgumentException::class,
	'Day value (32) is out of range.',
);

Assert::exception(
	fn() => DateTime::check(hour: -1),
	Nette\InvalidArgumentException::class,
	'Hour value (-1) is out of range.',
);

Assert::exception(
	fn() => DateTime::check(hour: 60),
	Nette\InvalidArgumentException::class,
	'Hour value (60) is out of range.',
);

Assert::exception(
	fn() => DateTime::check(minute: -1),
	Nette\InvalidArgumentException::class,
	'Minute value (-1) is out of range.',
);

Assert::exception(
	fn() => DateTime::check(minute: 60),
	Nette\InvalidArgumentException::class,
	'Minute value (60) is out of range.',
);

Assert::exception(
	fn() => DateTime::check(second: -1),
	Nette\InvalidArgumentException::class,
	'Second value (-1) is out of range.',
);

Assert::exception(
	fn() => DateTime::check(second: 60),
	Nette\InvalidArgumentException::class,
	'Second value (60) is out of range.',
);

Assert::exception(
	fn() => DateTime::check(microsecond: -1),
	Nette\InvalidArgumentException::class,
	'Microsecond value (-1) is out of range.',
);

Assert::exception(
	fn() => DateTime::check(microsecond: 1_000_000),
	Nette\InvalidArgumentException::class,
	'Microsecond value (1000000) is out of range.',
);

Assert::exception(
	fn() => DateTime::check(second: 1.5, microsecond: 500_000),
	Nette\InvalidArgumentException::class,
	'Combination of second and microsecond (1000000) is out of range.',
);
