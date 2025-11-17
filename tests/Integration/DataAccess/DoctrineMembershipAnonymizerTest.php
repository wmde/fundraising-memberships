<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipAnonymizer;
use WMDE\Fundraising\MembershipContext\Domain\AnonymizationException;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;

#[CoversClass( DoctrineMembershipAnonymizer::class )]
class DoctrineMembershipAnonymizerTest extends TestCase {

	private Connection $conn;

	private \DateTimeImmutable $now;

	private \DateTime $defaultExportTime;
	private const int MEMBERSHIP_ID = 1;
	private const int ANOTHER_MEMBERSHIP_ID = 2;

	public function setUp(): void {
		$this->conn = TestEnvironment::newInstance()->getFactory()->getConnection();
		$this->now = new \DateTimeImmutable();
		$this->defaultExportTime = \DateTime::createFromImmutable( $this->now->sub( new \DateInterval( 'PT1H' ) ) );
	}

	public function testGivenOneMembership_anonymizeAtCleansUpFields(): void {
		$this->insertExportedMembership( self::MEMBERSHIP_ID, $this->defaultExportTime );
		$anonymizer = new DoctrineMembershipAnonymizer( $this->conn );

		$anonymizer->anonymizeAt( $this->now );

		$this->assertMembershipIsAnonymized( self::MEMBERSHIP_ID );
	}

	public function testGivenOneMembership_anonymizeCleansUpFields(): void {
		$this->insertExportedMembership( self::MEMBERSHIP_ID, $this->defaultExportTime );
		$anonymizer = new DoctrineMembershipAnonymizer( $this->conn );
		$anonymizer->anonymizeWithIds( self::MEMBERSHIP_ID );

		$this->assertMembershipIsAnonymized( self::MEMBERSHIP_ID );
	}

	public function testGivenDeletedMembership_anonymizeWillCleanUpFields(): void {
		$oldDate = \DateTime::createFromImmutable( $this->now->sub( new \DateInterval( 'P2DT1M' ) ) );
		$this->insertDeletedMembership( self::MEMBERSHIP_ID );

		$anonymizer = new DoctrineMembershipAnonymizer( $this->conn );
		$anonymizer->anonymizeWithIds( self::MEMBERSHIP_ID );

		$this->assertMembershipIsAnonymized( self::MEMBERSHIP_ID );
	}

	public function testAnonymizeAtReturnsNumberOfAnonymizedMemberships(): void {
		// Insert memberships with different IDs but the same date
		$this->insertMembership( 1 );
		$this->insertMembership( 5 );
		$this->insertMembership( 9 );
		$this->insertMembership( 10 );
		$anonymizer = new DoctrineMembershipAnonymizer( $this->conn );

		$this->assertSame( 4, $anonymizer->anonymizeAt( $this->now ) );
	}

	public function testAnonymizeAtIgnoresExportSettingsAndGracePeriod(): void {
		// Insert memberships with different IDs but the same date
		$this->insertMembership( 1 );
		$this->insertExportedMembership( 5, $this->defaultExportTime );
		$this->insertMembership( 8 );
		$anonymizer = new DoctrineMembershipAnonymizer( $this->conn );

		$this->assertSame( 3, $anonymizer->anonymizeAt( $this->now ) );
	}

	public function testAnonymizeAtThrowsExceptionWhenIdDoesNotExist(): void {
		$anonymizer = new DoctrineMembershipAnonymizer( $this->conn );

		$this->expectException( AnonymizationException::class );

		$anonymizer->anonymizeWithIds( self::MEMBERSHIP_ID );
	}

	public function testAnonymizeAtTransformsDatabaseExceptions(): void {
		$anonymizer = new DoctrineMembershipAnonymizer( $this->givenThrowingDatabaseConnection() );

		$this->expectException( AnonymizationException::class );

		$anonymizer->anonymizeAt( $this->now );
	}

	public function testAnonymizeTransformsDatabaseExceptions(): void {
		$anonymizer = new DoctrineMembershipAnonymizer( $this->givenThrowingDatabaseConnection() );

		$this->expectException( AnonymizationException::class );

		$anonymizer->anonymizeWithIds( self::MEMBERSHIP_ID );
	}

	public function testAnonymizeThrowsExceptionWhenEntryIsUnexportedAndInGracePeriod(): void {
		$this->insertMembership( self::MEMBERSHIP_ID );
		$anonymizer = new DoctrineMembershipAnonymizer( $this->conn );

		$this->expectException( AnonymizationException::class );

		$anonymizer->anonymizeWithIds( self::MEMBERSHIP_ID );
	}

	public function testAnonymizeAllAnonymizesExportedMemberships(): void {
		$this->insertExportedMembership( self::MEMBERSHIP_ID, new \DateTime() );
		$anonymizer = new DoctrineMembershipAnonymizer( $this->conn );

		$anonymizer->anonymizeAll();

		$this->assertMembershipIsAnonymized( self::MEMBERSHIP_ID );
	}

	public function testAnonymizeAllPreservesModeratedUndeletedExportedMemberships(): void {
		$membership1 = $this->newMembershipRecord( self::MEMBERSHIP_ID );
		$membership1['status'] = MembershipApplication::STATUS_MODERATION;
		$membership2 = $this->newMembershipRecord( self::ANOTHER_MEMBERSHIP_ID );
		$membership2['status'] = MembershipApplication::STATUS_CANCELLED_MODERATION;
		$this->conn->insert( 'request', $membership1 );
		$this->conn->insert( 'request', $membership2 );
		$anonymizer = new DoctrineMembershipAnonymizer( $this->conn );

		$anonymizer->anonymizeAll();

		$this->assertMembershipIsUnAnonymized( $membership1 );
		$this->assertMembershipIsAnonymized( self::ANOTHER_MEMBERSHIP_ID );
	}

	private function givenThrowingDatabaseConnection(): Connection {
		$queryBuilderStub = $this->createStub( QueryBuilder::class );
		$queryBuilderStub->method( 'executeStatement' )
			->willThrowException( new \RuntimeException( 'Database Exception, thrown by test double' ) );
		$conn = $this->createStub( Connection::class );
		$conn->method( 'createQueryBuilder' )->willReturn( $queryBuilderStub );
		return $conn;
	}

	private function insertMembership( int $id = self::MEMBERSHIP_ID, ?\DateTime $creationDate = null ): void {
		$this->conn->insert( 'request', $this->newMembershipRecord( $id, $creationDate ) );
	}

	private function insertDeletedMembership( int $id ): void {
		$membership = $this->newMembershipRecord( $id );
		$membership['status'] = MembershipApplication::STATUS_CANCELED;

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
		];
	}

	private function assertMembershipIsAnonymized( int $membershipId ): void {
		$result = $this->conn->executeQuery(
			'SELECT anrede, firma, titel, name, vorname, nachname, strasse, plz, ort, email, iban, bic, dob FROM request WHERE id = :id',
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
			'SELECT id, anrede, firma, titel, name, vorname, nachname, strasse, plz, ort, email, iban, bic, dob, status, timestamp FROM request WHERE id = :id',
			[ 'id' => $expectedMembership['id'] ]
		);
		$row = $result->fetchAssociative();
		$this->assertEquals( $expectedMembership, $row );
	}
}
