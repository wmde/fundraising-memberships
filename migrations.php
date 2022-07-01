<?php

// This is the migrations configuration file for developing migrations locally
// DO NOT USE THIS IN PRODUCTION

return [
	'table_storage' => [
		'table_name' => 'doctrine_migration_versions',
		'version_column_name' => 'version',
		'version_column_length' => 1024,
		'executed_at_column_name' => 'executed_at',
		'execution_time_column_name' => 'execution_time',
	],

	'migrations_paths' => [
		'WMDE\Fundraising\MembershipContext\DataAccess\Migrations' => './src/DataAccess/Migrations',
	],

	'all_or_nothing' => true,
	'organize_migrations' => 'none',
	'connection' => null,
	'em' => null,
];
