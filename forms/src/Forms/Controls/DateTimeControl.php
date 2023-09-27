<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Forms\Controls;

use Nette;
use Nette\Forms\Form;
use Stringable;


/**
 * Selects date or time or date & time.
 * @extends BaseControl<\DateTimeInterface> */
class DateTimeControl extends BaseControl
{
	public const
		Date = 1,
		Time = 2,
		DateTime = 3;

	private int $mode;
	private bool $withSeconds;


	public function __construct(string|Stringable|null $label = null, int $mode = self::Date, bool $withSeconds = false)
	{
		$this->mode = $mode;
		$this->withSeconds = $withSeconds;
		parent::__construct($label);
		$this->control->step = $withSeconds ? 1 : null;
	}


	/**
	 * @param \DateTimeInterface|string|null $value
	 */
	public function setValue($value): static
	{
		$this->value = $value === null ? null : $this->normalize($value);
		return $this;
	}


	/**
	 * @param \DateTimeInterface|string $dt
	 */
	private function normalize($dt): \DateTimeImmutable
	{
		if (is_string($dt)) {
			$dt = new \DateTimeImmutable($dt); // createFromFormat() must not be used because it allows invalid values
		} elseif ($dt instanceof \DateTime) {
			$dt = \DateTimeImmutable::createFromMutable($dt);
		} elseif (!$dt instanceof \DateTimeInterface) {
			throw new Nette\InvalidArgumentException('Value must be DateTimeInterface or string or null, ' . get_debug_type($dt) . ' given.');
		}

		if ($this->mode === self::Date) {
			return $dt->setTime(0, 0);
		}
		$dt = $dt->setTime((int) $dt->format('H'), (int) $dt->format('i'), $this->withSeconds ? (int) $dt->format('s') : 0);
		return $this->mode === self::Time ? $dt->setDate(0, 1, 1) : $dt;
	}


	public function loadHttpData(): void
	{
		$value = $this->getHttpData(Nette\Forms\Form::DataText);
		$matches = is_string($value) && preg_match(match ($this->mode) {
			self::Date => '~^\d{4}-\d{2}-\d{2}$~',
			self::Time => '~^\d{2}:\d{2}(:\d{2}(\.\d+)?)?$~',
			self::DateTime => '~^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d+)?)?$~',
		}, $value);
		try {
			$this->value = $matches ? $this->normalize($value) : null;
		} catch (\Throwable) {
			$this->value = null;
		}
	}


	public function getControl(): Nette\Utils\Html
	{
		return parent::getControl()->addAttributes([
			'value' => $this->value ? $this->format($this->value) : null,
			'type' => match ($this->mode) {
				self::Date => 'date', self::Time => 'time', self::DateTime => 'datetime-local'
			},
		]);
	}


	private function format(\DateTimeInterface $dt): string
	{
		return $dt->format(match ($this->mode) {
			self::Date => 'Y-m-d',
			self::Time => $this->withSeconds ? 'H:i:s' : 'H:i',
			self::DateTime => $this->withSeconds ? 'Y-m-d\\TH:i:s' : 'Y-m-d\\TH:i',
		});
	}


	public function addRule(
		callable|string $validator,
		string|Stringable|null $errorMessage = null,
		mixed $arg = null,
	): static
	{
		if ($validator === Form::Min) {
			$this->control->min = $arg = $this->format($this->normalize($arg));
		} elseif ($validator === Form::Max) {
			$this->control->max = $arg = $this->format($this->normalize($arg));
		} elseif ($validator === Form::Range) {
			$this->control->min = isset($arg[0])
				? $arg[0] = $this->format($this->normalize($arg[0]))
				: null;
			$this->control->max = isset($arg[1])
				? $arg[1] = $this->format($this->normalize($arg[1]))
				: null;
		}

		return parent::addRule($validator, $errorMessage, $arg);
	}
}
