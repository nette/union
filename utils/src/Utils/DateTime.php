<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Utils;

use Nette;


/**
 * DateTime.
 */
class DateTime extends \DateTime implements \JsonSerializable
{
	use Nette\SmartObject;

	/** minute in seconds */
	public const MINUTE = 60;

	/** hour in seconds */
	public const HOUR = 60 * self::MINUTE;

	/** day in seconds */
	public const DAY = 24 * self::HOUR;

	/** week in seconds */
	public const WEEK = 7 * self::DAY;

	/** average month in seconds */
	public const MONTH = 2_629_800;

	/** average year in seconds */
	public const YEAR = 31_557_600;


	/**
	 * Creates a DateTime object from a string, UNIX timestamp, or other DateTimeInterface object.
	 * @throws \Exception if the date and time are not valid.
	 */
	public static function from(string|int|\DateTimeInterface|null $time): static
	{
		if ($time instanceof \DateTimeInterface) {
			return new static($time->format('Y-m-d H:i:s.u'), $time->getTimezone());

		} elseif (is_numeric($time)) {
			if ($time <= self::YEAR) {
				$time += time();
			}

			return (new static)->setTimestamp((int) $time);

		} else { // textual or null
			return new static((string) $time);
		}
	}


	/**
	 * Creates DateTime object.
	 * @throws Nette\InvalidArgumentException if the date and time are not valid.
	 */
	public static function fromParts(
		int $year,
		int $month,
		int $day,
		int $hour = 0,
		int $minute = 0,
		float $second = 0.0,
	): static
	{
		self::check($year, $month, $day, $hour, $minute, $second);
		return new static(sprintf('%04d-%02d-%02d %02d:%02d:%02.5F', $year, $month, $day, $hour, $minute, $second));
	}


	/**
	 * Returns new DateTime object formatted according to the specified format.
	 */
	public static function createFromFormat(
		string $format,
		string $datetime,
		string|\DateTimeZone|null $timezone = null,
	): static|false
	{
		if ($timezone === null) {
			$timezone = new \DateTimeZone(date_default_timezone_get());

		} elseif (is_string($timezone)) {
			$timezone = new \DateTimeZone($timezone);
		}

		$date = parent::createFromFormat($format, $datetime, $timezone);
		return $date ? static::from($date) : false;
	}


	/**
	 * Throws an exception if the date and time are not valid.
	 */
	public static function check(
		int $year = 1,
		int $month = 1,
		int $day = 1,
		int $hour = 0,
		int $minute = 0,
		float $second = 0,
		int $microsecond = 0,
	): void
	{
		$microsecond2 = round($second * 1_000_000) % 1_000_000 + $microsecond;
		match (true) {
			$month < 1 || $month > 12 => throw new Nette\InvalidArgumentException("Month value ($month) is out of range."),
			$day < 1 || $day > 31 => throw new Nette\InvalidArgumentException("Day value ($day) is out of range."),
			$hour < 0 || $hour > 23 => throw new Nette\InvalidArgumentException("Hour value ($hour) is out of range."),
			$minute < 0 || $minute > 59 => throw new Nette\InvalidArgumentException("Minute value ($minute) is out of range."),
			$second < 0 || $second >= 60 => throw new Nette\InvalidArgumentException("Second value ($second) is out of range."),
			$microsecond < 0 || $microsecond >= 1_000_000 => throw new Nette\InvalidArgumentException("Microsecond value ($microsecond) is out of range."),
			$microsecond2 >= 1_000_000 => throw new Nette\InvalidArgumentException("Combination of second and microsecond ($microsecond2) is out of range."),
			!checkdate($month, $day, $year) => throw new Nette\InvalidArgumentException('The date ' . sprintf('%04d-%02d-%02d', $year, $month, $day) . ' is not valid.'),
			default => null,
		};
	}


	public function __construct(string $datetime = 'now', ?\DateTimeZone $timezone = null)
	{
		parent::__construct($datetime, $timezone);
		$errors = self::getLastErrors();
		if ($errors && $errors['warnings']) {
			throw new Nette\InvalidArgumentException(Arrays::first($errors['warnings']) . " '$datetime'");
		}
	}


	public function setDate(int $year, int $month, int $day): static
	{
		self::check($year, $month, $day);
		return parent::setDate($year, $month, $day);
	}


	public function setTime(int $hour, int $minute, int $second = 0, int $microsecond = 0): static
	{
		self::check(hour: $hour, minute: $minute, second: $second, microsecond: $microsecond);
		return parent::setTime($hour, $minute, $second, $microsecond);
	}


	/**
	 * Returns JSON representation in ISO 8601 (used by JavaScript).
	 */
	public function jsonSerialize(): string
	{
		return $this->format('c');
	}


	/**
	 * Returns the date and time in the format 'Y-m-d H:i:s'.
	 */
	public function __toString(): string
	{
		return $this->format('Y-m-d H:i:s');
	}


	/**
	 * You'd better use: (clone $dt)->modify(...)
	 */
	public function modifyClone(string $modify = ''): static
	{
		$dolly = clone $this;
		return $modify ? $dolly->modify($modify) : $dolly;
	}
}
