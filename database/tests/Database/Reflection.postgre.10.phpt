<?php

/**
 * Test: PostgreSQL 10+ specific reflection
 * @dataProvider? databases.ini  postgresql
 */

declare(strict_types=1);

use Tester\Assert;
use Tester\Environment;

require __DIR__ . '/connect.inc.php'; // create $connection


$ver = $connection->query('SHOW server_version')->fetchField();
if (version_compare($ver, '10') < 0) {
	Environment::skip("For PostgreSQL 10 or newer but running with $ver.");
}


function shortInfo(array $columns): array
{
	return array_map(function (array $col): array {
		return [
			'name' => $col['name'],
			'autoincrement' => $col['autoincrement'],
			'sequence' => $col['vendor']['sequence'],
		];
	}, $columns);
}


test('SERIAL and IDENTITY imply autoincrement on primary keys', function () use ($connection) {
	Nette\Database\Helpers::loadFromFile($connection, Tester\FileMock::create('
		DROP SCHEMA IF EXISTS "reflection_10" CASCADE;
		CREATE SCHEMA "reflection_10";

		CREATE TABLE "reflection_10"."serial" ("id" SERIAL);
		CREATE TABLE "reflection_10"."serial_pk" ("id" SERIAL PRIMARY KEY);

		CREATE TABLE "reflection_10"."identity_always" ("id" INTEGER GENERATED ALWAYS AS IDENTITY);
		CREATE TABLE "reflection_10"."identity_always_pk" ("id" INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY);

		CREATE TABLE "reflection_10"."identity_by_default" ("id" INTEGER GENERATED BY DEFAULT AS IDENTITY);
		CREATE TABLE "reflection_10"."identity_by_default_pk" ("id" INTEGER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY);
	'));

	$driver = $connection->getDriver();

	$connection->query('SET search_path TO reflection_10');

	$columns = [
		'serial' => shortInfo($driver->getColumns('serial')),
		'serial_pk' => shortInfo($driver->getColumns('serial_pk')),
		'identity_always' => shortInfo($driver->getColumns('identity_always')),
		'identity_always_pk' => shortInfo($driver->getColumns('identity_always_pk')),
		'identity_by_default' => shortInfo($driver->getColumns('identity_by_default')),
		'identity_by_default_pk' => shortInfo($driver->getColumns('identity_by_default_pk')),
	];

	Assert::same([
		'serial' => [[
			'name' => 'id',
			'autoincrement' => false,
			'sequence' => 'serial_id_seq',
		]],

		'serial_pk' => [[
			'name' => 'id',
			'autoincrement' => true,
			'sequence' => 'serial_pk_id_seq',
		]],

		'identity_always' => [[
			'name' => 'id',
			'autoincrement' => false,
			'sequence' => 'identity_always_id_seq',
		]],

		'identity_always_pk' => [[
			'name' => 'id',
			'autoincrement' => true,
			'sequence' => 'identity_always_pk_id_seq',
		]],

		'identity_by_default' => [[
			'name' => 'id',
			'autoincrement' => false,
			'sequence' => 'identity_by_default_id_seq',
		]],

		'identity_by_default_pk' => [[
			'name' => 'id',
			'autoincrement' => true,
			'sequence' => 'identity_by_default_pk_id_seq',
		]],
	], $columns);
});


test('Materialized view columns', function () use ($connection) {
	Nette\Database\Helpers::loadFromFile($connection, Tester\FileMock::create('
		DROP SCHEMA IF EXISTS "reflection_10" CASCADE;
		CREATE SCHEMA "reflection_10";

		CREATE TABLE "reflection_10"."source" (
			"id" INTEGER,
			"name" TEXT
		);

		CREATE MATERIALIZED VIEW "reflection_10"."source_mt" AS SELECT "name", "id" FROM "reflection_10"."source";
	'));

	$driver = $connection->getDriver();

	$connection->query('SET search_path TO reflection_10');

	Assert::same([
		['name' => 'source', 'view' => false, 'fullName' => 'reflection_10.source'],
		['name' => 'source_mt', 'view' => true, 'fullName' => 'reflection_10.source_mt'],
	], $driver->getTables());

	Assert::same(
		['name', 'id'],
		array_column($driver->getColumns('source_mt'), 'name')
	);
});


test('Partitioned table', function () use ($connection) {
	Nette\Database\Helpers::loadFromFile($connection, Tester\FileMock::create('
		DROP SCHEMA IF EXISTS "reflection_10" CASCADE;
		CREATE SCHEMA "reflection_10";

		CREATE TABLE "reflection_10"."parted" (
			"id" INTEGER PRIMARY KEY,
			"value" INTEGER
		) PARTITION BY RANGE (id);

		CREATE TABLE "reflection_10"."part_1" PARTITION OF "reflection_10"."parted" FOR VALUES FROM (1) TO (10);
	'));

	$driver = $connection->getDriver();

	$connection->query('SET search_path TO reflection_10');

	Assert::same([
		['name' => 'part_1', 'view' => false, 'fullName' => 'reflection_10.part_1'],
		['name' => 'parted', 'view' => false, 'fullName' => 'reflection_10.parted'],
	], $driver->getTables());

	Assert::same(['id', 'value'], array_column($driver->getColumns('parted'), 'name'));
	Assert::same(['id', 'value'], array_column($driver->getColumns('part_1'), 'name'));

	Assert::same([[
		'name' => 'parted_pkey',
		'unique' => true,
		'primary' => true,
		'columns' => ['id'],
	]], $driver->getIndexes('parted'));
});
