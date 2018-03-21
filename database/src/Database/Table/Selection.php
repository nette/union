<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Database\Table;

use Nette;
use Nette\Database\Context;
use Nette\Database\IConventions;


/**
 * Filtered table representation.
 * Selection is based on the great library NotORM http://www.notorm.com written by Jakub Vrana.
 */
class Selection implements \Iterator, IRowContainer, \ArrayAccess, \Countable
{
	use Nette\SmartObject;

	/** @var Context */
	protected $context;

	/** @var IConventions */
	protected $conventions;

	/** @var ColumnAccessCache */
	protected $cache;

	/** @var ReferenceCache */
	protected $refCache;

	/** @var array cache of Selection and GroupedSelection prototypes */
	protected $globalRefCache = [];

	/** @var SqlBuilder */
	protected $sqlBuilder;

	/** @var string table name */
	protected $name;

	/** @var string|array|null primary key field name */
	protected $primary;

	/** @var string|bool primary column sequence name, false for autodetection */
	protected $primarySequence = false;

	/** @var IRow[] data read from database in [primary key => IRow] format */
	protected $rows;

	/** @var IRow[] modifiable data in [primary key => IRow] format */
	protected $data = [];

	/** @var bool */
	protected $dataRefreshed = false;

	/** @var array of [conditions => [key => IRow]]; used by GroupedSelection */
	protected $aggregation = [];

	/** @var array of primary key values */
	protected $keys = [];


	/**
	 * Creates filtered table representation.
	 */
	public function __construct(Context $context, IConventions $conventions, string $tableName, Nette\Caching\IStorage $cacheStorage = null)
	{
		$this->context = $context;
		$this->conventions = $conventions;
		$this->name = $tableName;

		$this->cache = new ColumnAccessCache($this, $cacheStorage);
		$this->primary = $conventions->getPrimary($tableName);
		$this->sqlBuilder = new SqlBuilder($tableName, $context);

		$this->linkRefCache();
	}


	public function __destruct()
	{
		$this->cache->saveState();
	}


	public function __clone()
	{
		$this->cache = clone $this->cache;
		$this->cache->setSelection($this);
		$this->sqlBuilder = clone $this->sqlBuilder;
		$this->linkRefCache();
	}


	public function getName(): string
	{
		return $this->name;
	}


	/**
	 * @return string|array|null
	 */
	public function getPrimary(bool $throw = true)
	{
		if ($this->primary === null && $throw) {
			throw new \LogicException("Table '{$this->name}' does not have a primary key.");
		}
		return $this->primary;
	}


	public function getPrimarySequence(): ?string
	{
		if ($this->primarySequence === false) {
			$this->primarySequence = $this->context->getStructure()->getPrimaryKeySequence($this->name);
		}

		return $this->primarySequence;
	}


	/**
	 * @return static
	 */
	public function setPrimarySequence(string $sequence)
	{
		$this->primarySequence = $sequence;
		return $this;
	}


	public function getSql(): string
	{
		return $this->sqlBuilder->buildSelectQuery($this->cache->getPreviousAccessedColumns());
	}


	/**
	 * @internal
	 */
	public function getSqlBuilder(): SqlBuilder
	{
		return $this->sqlBuilder;
	}


	/**
	 * @internal
	 */
	public function getCache(): ColumnAccessCache
	{
		return $this->cache;
	}


	/**
	 * @internal
	 */
	public function &getGlobalRefCache(string $key)
	{
		return $this->globalRefCache[$key];
	}


	/**
	 * @internal
	 */
	public function getConnection(): Nette\Database\Connection
	{
		return $this->context->getConnection();
	}


	/********************* quick access ****************d*g**/


	/**
	 * Returns row specified by primary key.
	 * @param  mixed  $key  primary key
	 */
	public function get($key): ?ActiveRow
	{
		$clone = clone $this;
		return $clone->wherePrimary($key)->fetch();
	}


	/**
	 * @inheritDoc
	 * @return ActiveRow|null if there is no such row
	 */
	public function fetch(): ?Nette\Database\IRow
	{
		$this->execute();
		$return = current($this->data);
		next($this->data);
		return $return === false ? null : $return;
	}


	/**
	 * Fetches single field.
	 * @return mixed
	 * @deprecated
	 */
	public function fetchField(string $column = null)
	{
		if ($column) {
			$this->select($column);
		}

		$row = $this->fetch();
		if ($row) {
			return $column ? $row[$column] : array_values($row->toArray())[0];
		}

		return null;
	}


	/**
	 * @inheritDoc
	 */
	public function fetchPairs($key = null, $value = null): array
	{
		return Nette\Database\Helpers::toPairs($this->fetchAll(), $key, $value);
	}


	/**
	 * @inheritDoc
	 */
	public function fetchAll(): array
	{
		return iterator_to_array($this);
	}


	/**
	 * @inheritDoc
	 */
	public function fetchAssoc(string $path): array
	{
		$rows = array_map('iterator_to_array', $this->fetchAll());
		return Nette\Utils\Arrays::associate($rows, $path);
	}


	/********************* sql selectors ****************d*g**/


	/**
	 * Adds select clause, more calls appends to the end.
	 * @param  string|string[]  $columns  for example "column, MD5(column) AS column_md5"
	 * @return static
	 */
	public function select($columns, ...$params)
	{
		$this->emptyResultSet();
		$this->sqlBuilder->addSelect($columns, ...$params);
		return $this;
	}


	/**
	 * Adds condition for primary key.
	 * @param  mixed  $key
	 * @return static
	 */
	public function wherePrimary($key)
	{
		if (is_array($this->primary) && Nette\Utils\Arrays::isList($key)) {
			if (isset($key[0]) && is_array($key[0])) {
				$this->where($this->primary, $key);
			} else {
				foreach ($this->primary as $i => $primary) {
					$this->where($this->name . '.' . $primary, $key[$i]);
				}
			}
		} elseif (is_array($key) && !Nette\Utils\Arrays::isList($key)) { // key contains column names
			$this->where($key);
		} else {
			$this->where($this->name . '.' . $this->getPrimary(), $key);
		}

		return $this;
	}


	/**
	 * Adds where condition, more calls appends with AND.
	 * @param  string|string[]  $condition  possibly containing ?
	 * @return static
	 */
	public function where($condition, ...$params)
	{
		$this->condition($condition, $params);
		return $this;
	}


	/**
	 * Adds ON condition when joining specified table, more calls appends with AND.
	 * @param  string  $tableChain  table chain or table alias for which you need additional left join condition
	 * @param  string  $condition  possibly containing ?
	 * @return static
	 */
	public function joinWhere(string $tableChain, string $condition, ...$params)
	{
		$this->condition($condition, $params, $tableChain);
		return $this;
	}


	/**
	 * Adds condition, more calls appends with AND.
	 * @param  string|string[]  $condition  possibly containing ?
	 */
	protected function condition($condition, array $params, $tableChain = null): void
	{
		$this->emptyResultSet();
		if (is_array($condition) && $params === []) { // where(array('column1' => 1, 'column2 > ?' => 2))
			foreach ($condition as $key => $val) {
				if (is_int($key)) {
					$this->condition($val, [], $tableChain); // where('full condition')
				} else {
					$this->condition($key, [$val], $tableChain); // where('column', 1)
				}
			}
		} elseif ($tableChain) {
			$this->sqlBuilder->addJoinCondition($tableChain, $condition, ...$params);
		} else {
			$this->sqlBuilder->addWhere($condition, ...$params);
		}
	}


	/**
	 * Adds where condition using the OR operator between parameters.
	 * More calls appends with AND.
	 * @param  array  $parameters ['column1' => 1, 'column2 > ?' => 2, 'full condition']
	 * @return static
	 * @throws \Nette\InvalidArgumentException
	 */
	public function whereOr(array $parameters)
	{
		if (count($parameters) < 2) {
			return $this->where($parameters);
		}
		$columns = [];
		$values = [];
		foreach ($parameters as $key => $val) {
			if (is_int($key)) { // whereOr(['full condition'])
				$columns[] = $val;
			} elseif (strpos($key, '?') === false) { // whereOr(['column1' => 1])
				$columns[] = $key . ' ?';
				$values[] = $val;
			} else { // whereOr(['column1 > ?' => 1])
				$qNumber = substr_count($key, '?');
				if ($qNumber > 1 && (!is_array($val) || $qNumber !== count($val))) {
					throw new Nette\InvalidArgumentException('Argument count does not match placeholder count.');
				}
				$columns[] = $key;
				$values = array_merge($values, $qNumber > 1 ? $val : [$val]);
			}
		}
		$columnsString = '(' . implode(') OR (', $columns) . ')';
		return $this->where($columnsString, $values);
	}


	/**
	 * Adds order clause, more calls appends to the end.
	 * @param  string  $columns  for example 'column1, column2 DESC'
	 * @return static
	 */
	public function order(string $columns, ...$params)
	{
		$this->emptyResultSet();
		$this->sqlBuilder->addOrder($columns, ...$params);
		return $this;
	}


	/**
	 * Sets limit clause, more calls rewrite old values.
	 * @return static
	 */
	public function limit(int $limit, int $offset = null)
	{
		$this->emptyResultSet();
		$this->sqlBuilder->setLimit($limit, $offset);
		return $this;
	}


	/**
	 * Sets offset using page number, more calls rewrite old values.
	 * @return static
	 */
	public function page(int $page, int $itemsPerPage, &$numOfPages = null)
	{
		if (func_num_args() > 2) {
			$numOfPages = (int) ceil($this->count('*') / $itemsPerPage);
		}
		if ($page < 1) {
			$itemsPerPage = 0;
		}
		return $this->limit($itemsPerPage, ($page - 1) * $itemsPerPage);
	}


	/**
	 * Sets group clause, more calls rewrite old value.
	 * @return static
	 */
	public function group(string $columns, ...$params)
	{
		$this->emptyResultSet();
		$this->sqlBuilder->setGroup($columns, ...$params);
		return $this;
	}


	/**
	 * Sets having clause, more calls rewrite old value.
	 * @return static
	 */
	public function having(string $having, ...$params)
	{
		$this->emptyResultSet();
		$this->sqlBuilder->setHaving($having, ...$params);
		return $this;
	}


	/**
	 * Aliases table. Example ':book:book_tag.tag', 'tg'
	 * @return static
	 */
	public function alias(string $tableChain, string $alias)
	{
		$this->sqlBuilder->addAlias($tableChain, $alias);
		return $this;
	}


	/********************* aggregations ****************d*g**/


	/**
	 * Executes aggregation function.
	 * @param  string  $function  select call in "FUNCTION(column)" format
	 * @return mixed
	 */
	public function aggregation(string $function)
	{
		$selection = $this->createSelectionInstance();
		$selection->getSqlBuilder()->importConditions($this->getSqlBuilder());
		$selection->select($function);
		foreach ($selection->fetch() as $val) {
			return $val;
		}
	}


	/**
	 * Counts number of rows.
	 * @param  string  $column  if it is not provided returns count of result rows, otherwise runs new sql counting query
	 */
	public function count(string $column = null): int
	{
		if (!$column) {
			$this->execute();
			return count($this->data);
		}
		return $this->aggregation("COUNT($column)");
	}


	/**
	 * Returns minimum value from a column.
	 * @return mixed
	 */
	public function min(string $column)
	{
		return $this->aggregation("MIN($column)");
	}


	/**
	 * Returns maximum value from a column.
	 * @return mixed
	 */
	public function max(string $column)
	{
		return $this->aggregation("MAX($column)");
	}


	/**
	 * Returns sum of values in a column.
	 * @return mixed
	 */
	public function sum(string $column)
	{
		return $this->aggregation("SUM($column)");
	}


	/********************* internal ****************d*g**/


	protected function execute(): void
	{
		if ($this->rows !== null) {
			return;
		}

		$this->cache->setObserveCache($this);

		if ($this->primary === null && $this->sqlBuilder->getSelect() === null) {
			throw new Nette\InvalidStateException('Table with no primary key requires an explicit select clause.');
		}

		try {
			$result = $this->query($this->getSql());

		} catch (Nette\Database\DriverException $exception) {
			if ($this->sqlBuilder->getSelect() || !$this->cache->getPreviousAccessedColumns()) {
				throw $exception;
			}

			$this->cache->clearPreviousAccessedColumns();
			$this->cache->setAccessedColumns([]);
			$result = $this->query($this->getSql());
		}

		$this->rows = [];
		$usedPrimary = true;
		foreach ($result->getPdoStatement() as $key => $row) {
			$row = $this->createRow($result->normalizeRow($row));
			$primary = $row->getSignature(false);
			$usedPrimary = $usedPrimary && $primary !== '';
			$this->rows[$usedPrimary ? $primary : $key] = $row;
		}
		$this->data = $this->rows;

		if ($usedPrimary) {
			foreach ((array) $this->primary as $primary) {
				$this->cache->setAccessedColumn($primary, true);
			}
		}
	}


	protected function createRow(array $row): ActiveRow
	{
		return new ActiveRow($row, $this);
	}


	public function createSelectionInstance(string $table = null): self
	{
		return new self($this->context, $this->conventions, $table ?: $this->name, $this->cache->getStorage());
	}


	protected function createGroupedSelectionInstance(string $table, string $column): GroupedSelection
	{
		return new GroupedSelection($this->context, $this->conventions, $table, $column, $this, $this->cache->getStorage());
	}


	protected function query(string $query): Nette\Database\ResultSet
	{
		return $this->context->queryArgs($query, $this->sqlBuilder->getParameters());
	}


	protected function emptyResultSet(bool $clearCache = true, bool $deleteRererencedCache = true): void
	{
		if ($this->rows !== null && $clearCache) {
			$this->cache->saveState();
		}

		if ($clearCache) {
			// NOT NULL in case of missing some column
			$this->cache->clearPreviousAccessedColumns();
			$this->cache->setGeneralCacheKey(null);
		}

		$this->rows = null;
		$this->cache->setSpecificCacheKey(null);
		$this->refCache->clearReferencingPrototype();
		if ($deleteRererencedCache) {
			$this->refCache->clearReferenced();
		}
	}


	/**
	 * Returns Selection parent for caching.
	 * @return static
	 */
	protected function getRefTable(&$refPath)
	{
		return $this;
	}


	/**
	 * Loads refCache references
	 */
	protected function loadRefCache(): void
	{
	}


	/**
	 * Link refCache references
	 */
	protected function linkRefCache(): void
	{
		$refTable = $this->getRefTable($refPath);
		if ($refTable === $this) {
			$this->refCache = $this->globalRefCache[$refPath] = new ReferenceCache;
		} else {
			$this->refCache = &$refTable->getGlobalRefCache($refPath);
			if ($this->refCache === null) {
				$this->refCache = new ReferenceCache;
			}
		}
	}


	/**
	 * @internal
	 * @return bool if selection requeried for more columns.
	 */
	public function accessColumn(string $key, bool $selectColumn = true): bool
	{
		if (!$this->cache->getStorage()) {
			return false;
		}

		$this->cache->setAccessedColumn($key, $selectColumn);

		$previousAccessedColumns = $this->cache->getPreviousAccessedColumns();
		if ($selectColumn && $previousAccessedColumns && !in_array($key, $previousAccessedColumns, true) && !$this->sqlBuilder->getSelect()) {
			$this->refreshData();
		}

		return $this->dataRefreshed;
	}


	/**
	 * @internal
	 */
	public function removeAccessColumn(string $key): void
	{
		$this->cache->setAccessedColumn($key, false);
	}


	/**
	 * @internal
	 * @return bool if selection requeried for reload.
	 */
	public function reloadAllColumns(): bool
	{
		if (!$this->cache->getStorage()) {
			return false;
		}

		$this->cache->setAccessedColumns([]);
		$currentKey = key($this->data);

		$previousAccessedColumns = $this->cache->getPreviousAccessedColumns();
		if ($previousAccessedColumns && !$this->sqlBuilder->getSelect()) {
			$this->refreshData();

			// move iterator to specific key
			while (key($this->data) !== null && key($this->data) !== $currentKey) {
				next($this->data);
			}
		}

		return $this->dataRefreshed;
	}


	protected function refreshData(): void
	{
		if ($this->sqlBuilder->getLimit()) {
			$generalCacheKey = $this->cache->getGeneralCacheKey();
			$sqlBuilder = $this->sqlBuilder;

			$primaryValues = [];
			foreach ((array) $this->rows as $row) {
				$primary = $row->getPrimary();
				$primaryValues[] = is_array($primary) ? array_values($primary) : $primary;
			}

			$this->emptyResultSet(false);
			$this->sqlBuilder = clone $this->sqlBuilder;
			$this->sqlBuilder->setLimit(null, null);
			$this->wherePrimary($primaryValues);

			$this->cache->setGeneralCacheKey($generalCacheKey);
			$this->cache->setPreviousAccessedColumns([]);
			$this->execute();
			$this->sqlBuilder = $sqlBuilder;
		} else {
			$this->emptyResultSet(false);
			$this->cache->setPreviousAccessedColumns([]);
			$this->execute();
		}

		$this->dataRefreshed = true;
	}


	/********************* manipulation ****************d*g**/


	/**
	 * Inserts row in a table.
	 * @param  array|\Traversable|Selection  $data  array($column => $value)|\Traversable|Selection for INSERT ... SELECT
	 * @return ActiveRow|int|bool Returns IRow or number of affected rows for Selection or table without primary key
	 */
	public function insert(iterable $data)
	{
		if ($data instanceof self) {
			$return = $this->context->queryArgs($this->sqlBuilder->buildInsertQuery() . ' ' . $data->getSql(), $data->getSqlBuilder()->getParameters());

		} else {
			if ($data instanceof \Traversable) {
				$data = iterator_to_array($data);
			}
			$return = $this->context->query($this->sqlBuilder->buildInsertQuery() . ' ?values', $data);
		}

		$this->loadRefCache();

		if ($data instanceof self || $this->primary === null) {
			$this->refCache->unsetReferencing($this->cache->getGeneralCacheKey(), $this->cache->getSpecificCacheKey());
			return $return->getRowCount();
		}

		$primarySequenceName = $this->getPrimarySequence();
		$primaryAutoincrementKey = $this->context->getStructure()->getPrimaryAutoincrementKey($this->name);

		$primaryKey = [];
		foreach ((array) $this->primary as $key) {
			if (isset($data[$key])) {
				$primaryKey[$key] = $data[$key];
			}
		}

		// First check sequence
		if (!empty($primarySequenceName) && $primaryAutoincrementKey) {
			$primaryKey[$primaryAutoincrementKey] = $this->context->getInsertId($this->context->getConnection()->getSupplementalDriver()->delimite($primarySequenceName));

		// Autoincrement primary without sequence
		} elseif ($primaryAutoincrementKey) {
			$primaryKey[$primaryAutoincrementKey] = $this->context->getInsertId($primarySequenceName);

		// Multi column primary without autoincrement
		} elseif (is_array($this->primary)) {
			foreach ($this->primary as $key) {
				if (!isset($data[$key])) {
					return $data;
				}
			}

		// Primary without autoincrement, try get primary from inserting data
		} elseif ($this->primary && isset($data[$this->primary])) {
			$primaryKey = $data[$this->primary];

		// If primaryKey cannot be prepared, return inserted rows count
		} else {
			$this->refCache->unsetReferencing($this->cache->getGeneralCacheKey(), $this->cache->getSpecificCacheKey());
			return $return->getRowCount();
		}

		$row = $this->createSelectionInstance()
			->select('*')
			->wherePrimary($primaryKey)
			->fetch();

		if ($this->rows !== null) {
			if ($signature = $row->getSignature(false)) {
				$this->rows[$signature] = $row;
				$this->data[$signature] = $row;
			} else {
				$this->rows[] = $row;
				$this->data[] = $row;
			}
		}

		return $row;
	}


	/**
	 * Updates all rows in result set.
	 * Joins in UPDATE are supported only in MySQL
	 * @return int number of affected rows
	 */
	public function update(iterable $data): int
	{
		if ($data instanceof \Traversable) {
			$data = iterator_to_array($data);

		} elseif (!is_array($data)) {
			throw new Nette\InvalidArgumentException;
		}

		if (!$data) {
			return 0;
		}

		return $this->context->queryArgs(
			$this->sqlBuilder->buildUpdateQuery(),
			array_merge([$data], $this->sqlBuilder->getParameters())
		)->getRowCount();
	}


	/**
	 * Deletes all rows in result set.
	 * @return int number of affected rows
	 */
	public function delete(): int
	{
		return $this->query($this->sqlBuilder->buildDeleteQuery())->getRowCount();
	}


	/********************* references ****************d*g**/


	/**
	 * Returns referenced row.
	 * @return ActiveRow|null|false null if the row does not exist, false if the relationship does not exist
	 */
	public function getReferencedTable(ActiveRow $row, ?string $table, string $column = null)
	{
		if (!$column) {
			$belongsTo = $this->conventions->getBelongsToReference($this->name, $table);
			if (!$belongsTo) {
				return false;
			}
			[$table, $column] = $belongsTo;
		}
		if (!$row->accessColumn($column)) {
			return false;
		}

		$checkPrimaryKey = $row[$column];

		$referenced = &$this->refCache->getReferenced($this->cache->getSpecificCacheKey(), $table, $column);
		$selection = &$referenced['selection'];
		$cacheKeys = &$referenced['cacheKeys'];
		if ($selection === null || ($checkPrimaryKey !== null && !isset($cacheKeys[$checkPrimaryKey]))) {
			$this->execute();
			$cacheKeys = [];
			foreach ($this->rows as $row) {
				if ($row[$column] === null) {
					continue;
				}

				$key = $row[$column];
				$cacheKeys[$key] = true;
			}

			if ($cacheKeys) {
				$selection = $this->createSelectionInstance($table);
				$selection->where($selection->getPrimary(), array_keys($cacheKeys));
			} else {
				$selection = [];
			}
		}

		return $selection[$checkPrimaryKey] ?? null;
	}


	/**
	 * Returns referencing rows.
	 * @param  int  $active  primary key
	 */
	public function getReferencingTable(string $table, string $column = null, int $active = null): ?GroupedSelection
	{
		if (strpos($table, '.') !== false) {
			[$table, $column] = explode('.', $table);
		} elseif (!$column) {
			$hasMany = $this->conventions->getHasManyReference($this->name, $table);
			if (!$hasMany) {
				return null;
			}
			[$table, $column] = $hasMany;
		}

		$prototype = &$this->refCache->getReferencingPrototype($this->cache->getSpecificCacheKey(), $table, $column);
		if (!$prototype) {
			$prototype = $this->createGroupedSelectionInstance($table, $column);
			$prototype->where("$table.$column", array_keys((array) $this->rows));
		}

		$clone = clone $prototype;
		$clone->setActive($active);
		return $clone;
	}


	/********************* interface Iterator ****************d*g**/


	public function rewind(): void
	{
		$this->execute();
		$this->keys = array_keys($this->data);
		reset($this->keys);
	}


	/** @return ActiveRow|bool */
	public function current()
	{
		if (($key = current($this->keys)) !== false) {
			return $this->data[$key];
		}

		return false;
	}


	/**
	 * @return string|int row ID
	 */
	public function key()
	{
		return current($this->keys);
	}


	public function next(): void
	{
		do {
			next($this->keys);
		} while (($key = current($this->keys)) !== false && !isset($this->data[$key]));
	}


	public function valid(): bool
	{
		return current($this->keys) !== false;
	}


	/********************* interface ArrayAccess ****************d*g**/


	/**
	 * Mimic row.
	 * @param  string  $key
	 * @param  IRow  $value
	 */
	public function offsetSet($key, $value): void
	{
		$this->execute();
		$this->rows[$key] = $value;
	}


	/**
	 * Returns specified row.
	 * @param  string  $key
	 */
	public function offsetGet($key): ?ActiveRow
	{
		$this->execute();
		return $this->rows[$key];
	}


	/**
	 * Tests if row exists.
	 * @param  string  $key
	 */
	public function offsetExists($key): bool
	{
		$this->execute();
		return isset($this->rows[$key]);
	}


	/**
	 * Removes row from result set.
	 * @param  string  $key
	 */
	public function offsetUnset($key): void
	{
		$this->execute();
		unset($this->rows[$key], $this->data[$key]);
	}
}
