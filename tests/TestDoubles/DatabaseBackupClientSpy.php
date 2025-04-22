<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\DataAccess\Backup\DatabaseBackupClient;
use WMDE\Fundraising\MembershipContext\DataAccess\Backup\TableBackupConfiguration;

class DatabaseBackupClientSpy implements DatabaseBackupClient {
	/**
	 * @var TableBackupConfiguration[]
	 */
	private ?array $tableBackupConfigurations = null;

	public function backupMembershipTables( TableBackupConfiguration ...$backupConfigurations ): void {
		if ( $this->tableBackupConfigurations !== null ) {
			throw new \LogicException( "backupTable must only be called once!" );
		}
		$this->tableBackupConfigurations = $backupConfigurations;
	}

	/**
	 * @return TableBackupConfiguration[]
	 */
	public function getTableBackupConfigurations(): array {
		if ( $this->tableBackupConfigurations === null ) {
			throw new \LogicException( 'backupTable was never called!' );
		}
		return $this->tableBackupConfigurations;
	}

}
