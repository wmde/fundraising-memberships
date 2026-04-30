<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Clock\Clock;
use WMDE\Clock\SystemClock;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipAnonymizationMonitor;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;

#[CoversClass( DoctrineMembershipAnonymizationMonitor::class )]
class DoctrineMembershipAnonymizationMonitorTest extends TestCase {

	private Connection $conn;
	private DoctrineMembershipAnonymizationMonitor $monitor;

	private Clock $clock;

	public function setUp(): void {
		$this->conn = TestEnvironment::newInstance()->getFactory()->getConnection();
		$this->clock = new SystemClock();
		$this->monitor = new DoctrineMembershipAnonymizationMonitor( $this->conn, $this->clock );
	}

	public function testCountOldAbandonedModeratedMembershipApplications_ReturnsMinusOneOnError(): void {
		$throwingMonitor = new DoctrineMembershipAnonymizationMonitor( $this->givenThrowingDatabaseConnection(), $this->clock );

		$this->assertEquals( -1, $throwingMonitor->countOldAbandonedModeratedMembershipApplications() );
	}

	public function testCountOldAbandonedModeratedMembershipApplications_ExcludesRecentEntries(): void {
		// create older moderated membership
		$this->insertModeratedMembership(
			membershipId: 1,
			creationTime: $this->clock->now()->sub( new \DateInterval( 'P1Y' ) )
		);
		// create recent moderated membership
		$this->insertModeratedMembership(
			membershipId: 2,
			creationTime: $this->clock->now()->sub( new \DateInterval( 'P28D' ) )
		);
		$this->assertSame( 1, $this->monitor->countOldAbandonedModeratedMembershipApplications() );
	}

	public function testCountOldAbandonedModeratedMembershipApplications_OnlyIncludesEntriesStillContainingPersonalData(): void {
		// create older moderated membership with personal data
		$this->insertModeratedMembership(
			membershipId: 3,
			creationTime: $this->clock->now()->sub( new \DateInterval( 'P1Y' ) )
		);
		// create older moderated membership that got already exported and scrubbed (status, ...)
		$this->conn->insert(
			'request',
			$this->newScrubbedMembershipRecord(
				id: 4,
				creationDate: $this->clock->now()->sub( new \DateInterval( 'P1Y' ) )
			)
		);
		$this->assertMembershipIsAnonymized( 4 );
		$this->assertSame( 1, $this->monitor->countOldAbandonedModeratedMembershipApplications() );
	}

	public function testCountOldAbandonedModeratedMembershipApplications_OnlyIncludesModeratedEntries(): void {
		// create normal older membership
		$this->insertNonModeratedMembership(
			id: 5,
			creationTime: $this->clock->now()->sub( new \DateInterval( 'P1Y' ) )
		);
		// create moderated older membership
		$this->insertModeratedMembership(
			membershipId: 6,
			creationTime: $this->clock->now()->sub( new \DateInterval( 'P1Y' ) )
		);
		$this->assertSame( 1, $this->monitor->countOldAbandonedModeratedMembershipApplications() );
	}

	private function givenThrowingDatabaseConnection(): Connection {
		$queryBuilderStub = $this->createStub( QueryBuilder::class );
		$queryBuilderStub->method( 'executeQuery' )
			->willThrowException( new \RuntimeException( 'Database Exception, thrown by test double' ) );

		return $this->createConfiguredStub(
			Connection::class,
			[ 'createQueryBuilder' => $queryBuilderStub ]
		);
	}

	private function insertModeratedMembership( int $membershipId, \DateTimeImmutable $creationTime ): void {
		$membership = $this->newMembershipRecord( $membershipId, $creationTime );

		$membership['status'] = MembershipApplication::STATUS_MODERATION;

		$this->conn->insert( 'request', $membership );

		$this->conn->executeStatement(
			sql: 'INSERT INTO memberships_moderation_reasons (membership_id, moderation_reason_id) VALUES ( :membership_id, 1 );',
			params: [ 'membership_id' => $membershipId ]
		);
	}

	private function insertNonModeratedMembership( int $id, \DateTimeImmutable $creationTime ): void {
		$membership = $this->newMembershipRecord( $id, $creationTime );
		$membership['status'] = MembershipApplication::STATUS_CONFIRMED;
		$membership['timestamp'] = $creationTime->format( 'Y-m-d H:i:s' );

		$this->conn->insert( 'request', $membership );
	}

	/**
	 * @return array<string,string|int>
	 */
	private function newMembershipRecord( int $id, ?\DateTimeImmutable $creationDate = null ): array {
		$nowString = $this->clock->now()->format( 'Y-m-d H:i:s' );
		$creationString = $creationDate ? $creationDate->format( 'Y-m-d H:i:s' ) : $this->clock->now()->sub( new \DateInterval( 'P1D' ) )->format( 'Y-m-d H:i:s' );
		return [
			'id' => $id,
			'email' => 'ceo@ecorp.biz',
			'anrede' => 'Herr',
			'titel' => '',
			'firma' => 'E Corp',
			'name' => 'Phillip Price',
			'vorname' => 'Phillip',
			'nachname' => 'Price',
			'strasse' => '135 East 57th Street',
			'plz' => '12345',
			'ort' => 'New York City',
			'dob' => '1966-03-13',
			'iban' => 'DE02120300000000202051',
			'bic' => 'BYLADEM1001',
			'status' => MembershipApplication::STATUS_NEUTRAL,
			'backup' => $nowString,
			'timestamp' => $creationString,
			'is_scrubbed' => 0
		];
	}

	/**
	 * @return array<string,string|int|null>
	 */
	private function newScrubbedMembershipRecord( int $id, ?\DateTimeImmutable $creationDate = null ): array {
		$nowString = $this->clock->now()->format( 'Y-m-d H:i:s' );
		$creationString = $creationDate ? $creationDate->format( 'Y-m-d H:i:s' ) : $this->clock->now()->sub( new \DateInterval( 'P1D' ) )->format( 'Y-m-d H:i:s' );
		return [
			'id' => $id,
			'email' => '',
			'anrede' => '',
			'titel' => '',
			'firma' => '',
			'name' => '',
			'vorname' => '',
			'nachname' => '',
			'strasse' => '',
			'plz' => '',
			'ort' => '',
			'dob' => null,
			'iban' => '',
			'bic' => '',
			'status' => MembershipApplication::STATUS_NEUTRAL,
			'backup' => $nowString,
			'timestamp' => $creationString,
			'is_scrubbed' => 1
		];
	}

	private function assertMembershipIsAnonymized( int $membershipId ): void {
		$result = $this->conn->executeQuery(
			'SELECT anrede, firma, titel, name, vorname, nachname, strasse, plz, ort, email, iban, bic, dob, is_scrubbed FROM request WHERE id = :id',
			[ 'id' => $membershipId ]
		);
		$row = $result->fetchAssociative();
		$this->assertEquals( [
			'anrede' => '',
			'firma' => '',
			'titel' => '',
			'name' => '',
			'vorname' => '',
			'nachname' => '',
			'strasse' => '',
			'plz' => '',
			'ort' => '',
			'email' => '',
			'iban' => '',
			'bic' => '',
			'dob' => null,
			'is_scrubbed' => 1
		], $row );
	}
}
