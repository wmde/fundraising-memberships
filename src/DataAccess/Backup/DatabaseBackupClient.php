<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Backup;

/**
 * The backup client is responsible for getting the data out of the database and writing it somewhere.
 *
 * The membership bounded context only exposes the interface, the actual implementation
 * (e.g. with `shell_exec` calling `mysqldump`, etc.) is in the Fundraising Operation Center
 */
interface DatabaseBackupClient {
	/**
	 * @param TableBackupConfiguration ...$tableBackupConfigurations At least one table configuration.
	 *          The client implementation can decide to write all table output into one file or to split
	 *          them into individual files.
	 * @return void
	 */
	public function backupMembershipTables( TableBackupConfiguration ...$tableBackupConfigurations ): void;
}
