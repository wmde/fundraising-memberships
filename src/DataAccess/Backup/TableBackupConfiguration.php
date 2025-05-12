<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Backup;

class TableBackupConfiguration {

	/**
	 * @param string $tableName A database table name
	 * @param string $conditions SQL conditions to select the data that should be backed up from the table. Can be empty for "all data".
	 */
	public function __construct(
		public readonly string $tableName,
		public readonly string $conditions
	) {
	}
}
