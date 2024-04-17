<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Database\Mapping;

use Nette\Database\Row;
use Nette\Database\Table;


class EntityMapper
{
	public function createRow(array $row): Row
	{
		return new $class($row, $this);

	}


	public function createActiveRow(array $row, Table\Selection $selection): Table\ActiveRow
	{
		// TODO: automatické mapování enumů
		$class = $this->getClassName($selection->getName());
		$class = class_exists($class)
			? $class
			: Table\ActiveRow::class;
		return new $class($row, $this);

	}


	private function getClassName(string $table): string
	{
		return 'App\Entity\\' . self::snakeToPascalCase(self::singularize($table)) . 'Row';
	}


	private static function snakeToPascalCase(string $table): string
	{
		$table = strtr($table, '_', ' ');
		$table = ucwords($table);
		$table = str_replace(' ', '', $table);
		return $table;
	}


	private static function singularize(string $word): string
	{
		if (!str_ends_with($word, 's') || str_ends_with($word, 'ss')) {
			return $word;
		} elseif (str_ends_with($word, 'ies')) {
			return substr($word, 0, -3) . 'y';
		} elseif (str_ends_with($word, 'es')) {
			$tmp = substr($word, 0, -2);
			return str_ends_with($tmp, 'ch') || str_ends_with($tmp, 'sh')
				? $tmp
				: substr($word, 0, -1);
		} else {
			return substr($word, 0, -1);
		}
	}
}
