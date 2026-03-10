<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipAnonymizationMonitor;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;

#[CoversClass( DoctrineMembershipAnonymizationMonitor::class )]
class DoctrineMembershipAnonymizationMonitorTest extends TestCase {

	private Connection $conn;
	private DoctrineMembershipAnonymizationMonitor $monitor;

	private \DateTime $now;

	private \DateTime $defaultExportTime;
	private const int MEMBERSHIP_ID = 1;
	private const int ANOTHER_MEMBERSHIP_ID = 2;

	public function setUp(): void {
		$this->conn = TestEnvironment::newInstance()->getFactory()->getConnection();
		$this->now = new \DateTime();
		$this->monitor = new DoctrineMembershipAnonymizationMonitor( $this->conn );
	}

	public function testCountOldAbandonedModeratedMembershipApplications_ReturnsMinusOneOnError(): void {
		$throwingMonitor = new DoctrineMembershipAnonymizationMonitor( $this->givenThrowingDatabaseConnection() );

		$this->assertEquals( -1, $throwingMonitor->countOldAbandonedModeratedMembershipApplications() );
	}

	public function testCountOldAbandonedModeratedMembershipApplications_ExcludesRecentEntries(): void {
		// TODO
		// create older moderated membership
		$this->insertModeratedMembership(
			id: 1,
			creationTime: $this->now->sub( new \DateInterval( 'P1Y' ) )
		);
		// create recent moderated membership
		$this->insertModeratedMembership(
			id: 2,
			creationTime: $this->now
		);
		$this->assertSame( 1, $this->monitor->countOldAbandonedModeratedMembershipApplications() );
	}

	public function testCountOldAbandonedModeratedMembershipApplications_OnlyIncludesEntriesStillContainingPersonalData(): void {
		// TODO
		// create older moderated membership with personal data
		$this->insertModeratedMembership(
			id: 3,
			creationTime: $this->now->sub( new \DateInterval( 'P1Y' ) )
		);
		// create older moderated membership that got already exported and scrubbed (status, ...)
		$this->newScrubbedMembershipRecord(
			id: 4,
			creationDate: $this->now->sub( new \DateInterval( 'P1Y' ) )
		);
		$this->assertMembershipIsAnonymized( 4 );
		// expect report to contain 1
		$this->assertSame( 1, $this->monitor->countOldAbandonedModeratedMembershipApplications() );
	}

	public function testCountOldAbandonedModeratedMembershipApplications_OnlyIncludesModeratedEntries(): void {
		// TODO
		// create normal older membership
		$this->insertNonModeratedMembership(
			id: 5,
			creationTime: $this->now->sub( new \DateInterval( 'P1Y' ) )
		);
		// create moderated older membership
		$this->insertModeratedMembership(
			id: 6,
			creationTime: $this->now->sub( new \DateInterval( 'P1Y' ) )
		);
		// expect report to contain 1
		$this->assertSame( 1, $this->monitor->countOldAbandonedModeratedMembershipApplications() );
	}

	private function givenThrowingDatabaseConnection(): Connection {
		$queryBuilderStub = $this->createStub( QueryBuilder::class );
		$queryBuilderStub->method( 'executeStatement' )
			->willThrowException( new \RuntimeException( 'Database Exception, thrown by test double' ) );

		return $this->createConfiguredStub(
			Connection::class,
			[ 'createQueryBuilder' => $queryBuilderStub ]
		);
	}

	private function insertMembership( int $id = self::MEMBERSHIP_ID, ?\DateTime $creationDate = null ): void {
		$this->conn->insert( 'request', $this->newMembershipRecord( $id, $creationDate ) );
	}

	private function insertDeletedMembership( int $id ): void {
		$membership = $this->newMembershipRecord( $id );
		$membership['status'] = MembershipApplication::STATUS_CANCELED;

		$this->conn->insert( 'request', $membership );
	}

	private function insertModeratedMembership( int $id, \DateTime $creationTime ): void {
		$membership = $this->newMembershipRecord( $id );
		$membership['status'] = MembershipApplication::STATUS_MODERATION;
		$membership['timestamp'] = $creationTime->format( 'Y-m-d H:i:s' );

		$this->conn->insert( 'request', $membership );
	}

	private function insertNonModeratedMembership( int $id, \DateTime $creationTime ): void {
		$membership = $this->newMembershipRecord( $id );
		$membership['status'] = MembershipApplication::STATUS_CONFIRMED;
		$membership['timestamp'] = $creationTime->format( 'Y-m-d H:i:s' );

		$this->conn->insert( 'request', $membership );
	}

	private function insertExportedMembership( int $id, \DateTime $exportTime ): void {
		$membership = $this->newMembershipRecord( $id );
		$membership['export'] = $exportTime->format( 'Y-m-d H:i:s' );
		$this->conn->insert( 'request', $membership );
	}

	/**
	 * @param int $id
	 * @return array<string,string|int>
	 */
	private function newMembershipRecord( int $id, ?\DateTime $creationDate = null ): array {
		$nowString = $this->now->format( 'Y-m-d H:i:s' );
		$creationString = $creationDate ? $creationDate->format( 'Y-m-d H:i:s' ) : $this->now->sub( new \DateInterval( 'P1D' ) )->format( 'Y-m-d H:i:s' );
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
			'dob' => '1966-03-13 13:13:13',
			'iban' => 'DE02120300000000202051',
			'bic' => 'BYLADEM1001',
			'status' => MembershipApplication::STATUS_NEUTRAL,
			'backup' => $nowString,
			'timestamp' => $creationString,
			'is_scrubbed' => 0
		];
	}

	private function newScrubbedMembershipRecord( int $id, ?\DateTime $creationDate = null ): array {
		$nowString = $this->now->format( 'Y-m-d H:i:s' );
		$creationString = $creationDate ? $creationDate->format( 'Y-m-d H:i:s' ) : $this->now->sub( new \DateInterval( 'P1D' ) )->format( 'Y-m-d H:i:s' );
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
			'iban' => 'DE02120300000000202051',
			'bic' => 'BYLADEM1001',
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

	/**
	 * @param array<string,scalar> $expectedMembership
	 * @return void
	 * @throws \Doctrine\DBAL\Exception
	 */
	private function assertMembershipIsUnAnonymized( array $expectedMembership ): void {
		unset( $expectedMembership['backup'] );
		$result = $this->conn->executeQuery(
			'SELECT id, anrede, firma, titel, name, vorname, nachname, strasse, plz, ort, email, iban, bic, dob, status, timestamp, is_scrubbed FROM request WHERE id = :id',
			[ 'id' => $expectedMembership['id'] ]
		);
		$row = $result->fetchAssociative();
		$this->assertEquals( $expectedMembership, $row );
	}
}
