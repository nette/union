<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Nette\Database;

use Nette;


/**
 * Cached reflection of database structure.
 *
 * @author     Jan Skrasek
 */
class Structure extends Nette\Object implements IStructure
{
	/** @var Nette\Database\Connection */
	protected $connection;

	/** @var Nette\Caching\Cache */
	protected $cache;

	/** @var array */
	protected $structure;

	/** @var bool */
	protected $isRebuilt = FALSE;


	public function __construct(Nette\Database\Connection $connection, Nette\Caching\IStorage $cacheStorage)
	{
		$this->connection = $connection;
		$this->cache = new Nette\Caching\Cache($cacheStorage, 'Nette.Database.Structure.' . md5($this->connection->getDsn()));
	}


	public function getTables()
	{
		$this->needStructure();
		return $this->structure['tables'];
	}


	public function getColumns($table)
	{
		$this->needStructure();
		$table = strtolower($table);

		if (!isset($this->structure['columns'][$table])) {
			throw new Nette\InvalidArgumentException("Table '$table' does not exist.");
		}

		return $this->structure['columns'][$table];
	}


	public function getPrimaryKey($table)
	{
		$this->needStructure();
		$table = strtolower($table);

		if (!isset($this->structure['primary'][$table])) {
			return NULL;
		}

		return $this->structure['primary'][$table];
	}


	public function getHasManyReference($table, $targetTable = NULL)
	{
		$this->needStructure();
		$table = strtolower($table);

		if ($targetTable) {
			$targetTable = strtolower($targetTable);
			if (!isset($this->structure['hasMany'][$table][$targetTable])) {
				return NULL;
			}
			return $this->structure['hasMany'][$table][$targetTable];

		} else {
			if (!isset($this->structure['hasMany'][$table])) {
				return array();
			}
			return $this->structure['hasMany'][$table];
		}
	}


	public function getBelongsToReference($table, $column = NULL)
	{
		$this->needStructure();
		$table = strtolower($table);

		if ($column) {
			$column = strtolower($column);
			if (!isset($this->structure['belongsTo'][$table][$column])) {
				return NULL;
			}
			return $this->structure['belongsTo'][$table][$column];

		} else {
			if (!isset($this->structure['belongsTo'][$table])) {
				return array();
			}
			return $this->structure['belongsTo'][$table];
		}
	}


	public function rebuild()
	{
		$this->structure = $this->loadStructure();
		$this->cache->save('structure', $this->structure);
		$this->isRebuilt = TRUE;
	}


	public function isRebuilt()
	{
		return $this->isRebuilt;
	}


	protected function needStructure()
	{
		if ($this->structure !== NULL) {
			return;
		}

		$this->structure = $this->cache->load('structure', array($this, 'loadStructure'));
	}


	/**
	 * @internal
	 * @ignore
	 */
	public function loadStructure()
	{
		$driver = $this->connection->getSupplementalDriver();

		$structure = array();
		$structure['tables'] = $driver->getTables();

		foreach ($structure['tables'] as $tablePair) {
			$table = $tablePair['name'];
			$structure['columns'][strtolower($table)] = $columns = $driver->getColumns($table);
			$structure['primary'][strtolower($table)] = $this->analyzePrimaryKey($columns);
			$this->analyzeForeignKeys($structure, $table);
		}

		if (isset($structure['hasMany'])) {
			foreach ($structure['hasMany'] as & $table) {
				uksort($table, function($a, $b) {
					return strlen($a) - strlen($b);
				});
			}
		}

		return $structure;
	}


	protected function analyzePrimaryKey(array $columns)
	{
		$primary = array();
		foreach ($columns as $column) {
			if ($column['primary']) {
				$primary[] = $column['name'];
			}
		}

		if (count($primary) === 0) {
			return NULL;
		} elseif (count($primary) === 1) {
			return reset($primary);
		} else {
			return $primary;
		}
	}


	protected function analyzeForeignKeys(& $structure, $table)
	{
		foreach ($this->connection->getSupplementalDriver()->getForeignKeys($table) as $row) {
			$structure['belongsTo'][strtolower($table)][$row['local']] = $row['table'];
			$structure['hasMany'][strtolower($row['table'])][$table][] = $row['local'];
		}

		if (isset($structure['belongsTo'][$table])) {
			uksort($structure['belongsTo'][$table], function($a, $b) {
				return strlen($a) - strlen($b);
			});
		}
	}

}
