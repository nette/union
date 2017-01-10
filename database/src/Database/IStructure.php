<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Database;


/**
 * Provides cached reflection for database structure.
 */
interface IStructure
{
	const
		FIELD_TEXT = 'string',
		FIELD_BINARY = 'bin',
		FIELD_BOOL = 'bool',
		FIELD_INTEGER = 'int',
		FIELD_FLOAT = 'float',
		FIELD_DATE = 'date',
		FIELD_TIME = 'time',
		FIELD_DATETIME = 'datetime',
		FIELD_UNIX_TIMESTAMP = 'timestamp',
		FIELD_TIME_INTERVAL = 'timeint';

	/**
	 * Returns tables list.
	 * @return array
	 */
	function getTables();

	/**
	 * Returns table columns list.
	 * @param  string
	 * @return array
	 */
	function getColumns($table);

	/**
	 * Returns table primary key.
	 * @param  string
	 * @return string|array|NULL
	 */
	function getPrimaryKey($table);

	/**
	 * Returns autoincrement primary key name.
	 * @param  string
	 * @return string|NULL
	 */
	//function getPrimaryAutoincrementKey($table);

	/**
	 * Returns table primary key sequence.
	 * @param  string
	 * @return string|NULL
	 */
	function getPrimaryKeySequence($table);

	/**
	 * Returns hasMany reference.
	 * If a targetTable is not provided, returns references for all tables.
	 * @param  string
	 * @param  string|NULL
	 * @return array|NULL
	 */
	function getHasManyReference($table, $targetTable = NULL);

	/**
	 * Returns belongsTo reference.
	 * If a column is not provided, returns references for all columns.
	 * @param  string
	 * @param  string|NULL
	 * @return array|NULL
	 */
	function getBelongsToReference($table, $column = NULL);

	/**
	 * Rebuilds database structure cache.
	 * @return void
	 */
	function rebuild();

	/**
	 * Returns true if database cached structure has been rebuilt.
	 * @return bool
	 */
	function isRebuilt();

}
