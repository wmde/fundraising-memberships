<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\Backup\DatabaseBackupClient;
use WMDE\Fundraising\MembershipContext\DataAccess\Backup\PersonalDataBackup;
use WMDE\Fundraising\MembershipContext\DataAccess\Backup\TableBackupConfiguration;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\DatabaseBackupClientSpy;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;

#[CoversClass( PersonalDataBackup::class )]
class PersonalDataBackupTest extends TestCase {
	private const string BACKUP_TIME = '2025-04-03 1:02:00';

	public function testBackupClientIsCalledWithMembershipTablesAndConditions(): void {
		$backupClientSpy = new DatabaseBackupClientSpy();
		$personalBackup = $this->givenPersonalBackup( backupClient: $backupClientSpy );

		$personalBackup->doBackup( $this->givenBackupTime() );

		$backupConfigurations = $backupClientSpy->getTableBackupConfigurations();
		$this->assertCount( 1, $backupConfigurations );
		$this->assertEquals(
			new TableBackupConfiguration( 'request', 'backup IS NULL' ),
			$backupConfigurations[0]
		);
	}

	public function testUnmarkedMembershipsGetMarkedAsBackedUp(): void {
		$factory = TestEnvironment::newInstance()->getFactory();
		$entityManager = $factory->getEntityManager();
		$this->givenMemberships( $entityManager );
		$personalBackup = $this->givenPersonalBackup( entityManager: $entityManager );

		$personalBackup->doBackup( $this->givenBackupTime() );

		$qb = $entityManager->createQueryBuilder();
		$qb->select( 'COUNT( m ) as updated_memberships' )
			->from( MembershipApplication::class, 'm' )
			->where( 'm.backup = :backupTime' )
			->setParameter( 'backupTime', $this->givenBackupTime() );
		$this->assertSame( [ [ 'updated_memberships' => 4 ] ], $qb->getQuery()->getScalarResult() );
	}

	public function testDoBackupReturnsNumberOfAffectedMemberships(): void {
		$factory = TestEnvironment::newInstance()->getFactory();
		$entityManager = $factory->getEntityManager();
		$this->givenMemberships( $entityManager );
		$personalBackup = $this->givenPersonalBackup( entityManager: $entityManager );

		$affectedDonations = $personalBackup->doBackup( $this->givenBackupTime() );

		$this->assertSame( 4, $affectedDonations );
	}

	private function givenPersonalBackup( ?DatabaseBackupClientSpy $backupClient = null, ?EntityManager $entityManager = null ): PersonalDataBackup {
		if ( $backupClient === null ) {
			$backupClient = $this->createStub( DatabaseBackupClient::class );
		}
		if ( $entityManager === null ) {
			$factory = TestEnvironment::newInstance()->getFactory();
			$entityManager = $factory->getEntityManager();
		}
		return new PersonalDataBackup( $backupClient, $entityManager );
	}

	private function givenBackupTime(): \DateTimeImmutable {
		return new \DateTimeImmutable( self::BACKUP_TIME );
	}

	private function givenMemberships( EntityManager $entityManager ): void {
		$entityManager->persist( ValidMembershipApplication::newDoctrineEntity( 1 ) );
		$entityManager->persist( ValidMembershipApplication::newDoctrineEntity( 2 ) );
		$entityManager->persist( ValidMembershipApplication::newDoctrineCompanyEntity( 3 ) );
		$entityManager->persist( ValidMembershipApplication::newDoctrineCompanyEntity( 4 ) );

		$entityManager->persist( ValidMembershipApplication::newAnonymizedDoctrineEntity( 10, new \DateTime( '2025-03-03 0:00:00' ) ) );
		$entityManager->persist( ValidMembershipApplication::newAnonymizedDoctrineEntity( 11, new \DateTime() ) );

		$entityManager->flush();
	}
}
