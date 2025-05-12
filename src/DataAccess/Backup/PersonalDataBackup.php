<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Backup;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;

class PersonalDataBackup {

	public function __construct(
		private readonly DatabaseBackupClient $backupClient,
		private readonly EntityManager $entityManager
	) {
	}

	public function doBackup( \DateTimeImmutable $backupTime ): int {
		$this->backupClient->backupMembershipTables(
			new TableBackupConfiguration( 'request', 'backup IS NULL' )
		);

		$qb = $this->entityManager->createQueryBuilder();
		$qb->update( MembershipApplication::class, 'm' )
			->set( 'm.backup', ':backupTime' )
			->where( 'm.backup IS NULL ' )
			->setParameter( 'backupTime', $backupTime );

		/** @var int $affectedRows */
		$affectedRows = $qb->getQuery()->execute();

		// Clear all lingering entities, they don't get changed by the update query
		// See https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/reference/dql-doctrine-query-language.html#update-queries
		$this->entityManager->clear();

		return $affectedRows;
	}
}
