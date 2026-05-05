<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipAnonymizer;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipRepository;
use WMDE\Fundraising\MembershipContext\DataAccess\ModerationReasonRepository;
use WMDE\Fundraising\MembershipContext\Domain\AnonymizationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FakePaymentAnonymizer;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentData;
use WMDE\Fundraising\PaymentContext\Domain\PaymentAnonymizer;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

#[CoversClass( DoctrineMembershipAnonymizer::class )]
class DoctrineMembershipAnonymizerTest extends TestCase {

	private Connection $conn;
	private EntityManager $entityManager;

	private \DateTimeImmutable $now;

	private \DateTime $defaultExportTime;
	private const int MEMBERSHIP_ID = 1;
	private const int PAYMENT_ID = 42;
	private const int ANOTHER_MEMBERSHIP_ID = 2;

	public function setUp(): void {
		$factory = TestEnvironment::newInstance()->getFactory();
		$this->conn = $factory->getConnection();
		$this->entityManager = $factory->getEntityManager();
		$this->now = new \DateTimeImmutable();
		$this->defaultExportTime = \DateTime::createFromImmutable( $this->now->sub( new \DateInterval( 'PT1H' ) ) );
	}

	private function newDoctrineMembershipAnonymizer( ?DoctrineMembershipRepository $repository = null, ?PaymentAnonymizer $paymentAnonymizer = null ): DoctrineMembershipAnonymizer {
		return new DoctrineMembershipAnonymizer(
			$repository ?? new DoctrineMembershipRepository( $this->entityManager, $this->makeGetPaymentUseCaseStub(), new ModerationReasonRepository( $this->entityManager ) ),
			$this->entityManager,
			$paymentAnonymizer ?? new FakePaymentAnonymizer()
		);
	}

	public function testGivenOneMembership_anonymizeWithIdsCleansUpFields(): void {
		$this->insertExportedMembership( self::MEMBERSHIP_ID, $this->defaultExportTime );
		$anonymizer = $this->newDoctrineMembershipAnonymizer();
		$anonymizer->anonymizeWithIds( self::MEMBERSHIP_ID );

		$this->assertMembershipIsAnonymized( self::MEMBERSHIP_ID );
	}

	public function testGivenDeletedMembership_anonymizeWithIdsWillCleanUpFields(): void {
		$this->insertDeletedMembership( self::MEMBERSHIP_ID );

		$anonymizer = $this->newDoctrineMembershipAnonymizer();
		$anonymizer->anonymizeWithIds( self::MEMBERSHIP_ID );

		$this->assertMembershipIsAnonymized( self::MEMBERSHIP_ID );
	}

	public function testAnonymizeWithIdsAnonymizesPayments(): void {
		$paymentAnonymizer = new FakePaymentAnonymizer();
		$this->insertExportedMembership( 1, $this->defaultExportTime );
		$this->insertExportedMembership( 2, $this->defaultExportTime );

		$anonymizer = $this->newDoctrineMembershipAnonymizer( paymentAnonymizer: $paymentAnonymizer );
		$anonymizer->anonymizeWithIds( 1, 2 );

		$this->assertSame( [ self::PAYMENT_ID, self::PAYMENT_ID ], $paymentAnonymizer->paymentIds );
	}

	public function testAnonymizeWithIdsThrowsExceptionWhenIdDoesNotExist(): void {
		$anonymizer = $this->newDoctrineMembershipAnonymizer();

		$this->expectException( AnonymizationException::class );

		$anonymizer->anonymizeWithIds( self::MEMBERSHIP_ID );
	}

	public function testAnonymizeWithIdsTransformsDatabaseExceptions(): void {
		$membershipRepository = $this->createMock( DoctrineMembershipRepository::class );
		$membershipRepository->method( 'getMembershipApplicationById' )->willReturn( ValidMembershipApplication::newApplication() );
		$membershipRepository->method( 'storeApplication' )->willThrowException( new StoreMembershipApplicationException( 'Could not store' ) );
		$anonymizer = $this->newDoctrineMembershipAnonymizer( repository: $membershipRepository );

		$this->expectException( AnonymizationException::class );

		$anonymizer->anonymizeWithIds( self::MEMBERSHIP_ID );
	}

	public function testAnonymizeWithIdsThrowsExceptionWhenEntryIsUnexportedAndInGracePeriod(): void {
		$this->insertMembership( self::MEMBERSHIP_ID );
		$anonymizer = $this->newDoctrineMembershipAnonymizer();

		$this->expectException( AnonymizationException::class );

		$anonymizer->anonymizeWithIds( self::MEMBERSHIP_ID );
	}

	public function testAnonymizeAllAnonymizesExportedMemberships(): void {
		$this->insertExportedMembership( self::MEMBERSHIP_ID, new \DateTime() );
		$anonymizer = $this->newDoctrineMembershipAnonymizer();

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
		$anonymizer = $this->newDoctrineMembershipAnonymizer();

		$anonymizer->anonymizeAll();

		$this->assertMembershipIsUnAnonymized( $membership1 );
		$this->assertMembershipIsAnonymized( self::ANOTHER_MEMBERSHIP_ID );
	}

	public function testAnonymizeAllDoesNotAnonymizeAgain(): void {
		// We create a data entry that pretends to be scrubbed while still containing the full personal data
		// In production, such entries should not exist
		$membership = $this->newMembershipRecord( self::MEMBERSHIP_ID );
		$membership['is_scrubbed'] = 1;
		$membership['export'] = date( 'Y-m-d H:i:s' );
		$this->conn->insert( 'request', $membership );
		$anonymizer = $this->newDoctrineMembershipAnonymizer();
		$expectedMembership = $membership;
		// assertMembershipIsUnAnonymized does not check export field
		unset( $expectedMembership['export'] );

		$anonymizer->anonymizeAll();

		$this->assertMembershipIsUnAnonymized( $expectedMembership );
	}

	public function testAnonymizeAllAnonymizesPayments(): void {
		$paymentAnonymizer = new FakePaymentAnonymizer();
		$this->insertExportedMembership( 1, $this->defaultExportTime );
		$this->insertExportedMembership( 2, $this->defaultExportTime );

		$anonymizer = $this->newDoctrineMembershipAnonymizer( paymentAnonymizer: $paymentAnonymizer );
		$anonymizer->anonymizeAll();

		$this->assertSame( [ self::PAYMENT_ID, self::PAYMENT_ID ], $paymentAnonymizer->paymentIds );
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
			'dob' => '1966-03-13',
			'iban' => 'DE02120300000000202051',
			'bic' => 'BYLADEM1001',
			'status' => MembershipApplication::STATUS_NEUTRAL,
			'backup' => $nowString,
			'timestamp' => $creationString,
			'is_scrubbed' => 0,
			'payment_id' => self::PAYMENT_ID
		];
	}

	private function assertMembershipIsAnonymized( int $membershipId ): void {
		$result = $this->conn->executeQuery(
			'SELECT anrede, firma, titel, name, vorname, nachname, strasse, plz, ort, email, iban, bic, dob, is_scrubbed, payment_id FROM request WHERE id = :id',
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
			'is_scrubbed' => 1,
			'payment_id' => self::PAYMENT_ID
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
			'SELECT id, anrede, firma, titel, name, vorname, nachname, strasse, plz, ort, email, iban, bic, dob, status, timestamp, is_scrubbed, payment_id FROM request WHERE id = :id',
			[ 'id' => $expectedMembership['id'] ]
		);
		$row = $result->fetchAssociative();
		$this->assertEquals( $expectedMembership, $row );
	}

	private function makeGetPaymentUseCaseStub(): GetPaymentUseCase {
		return $this->createConfiguredStub(
			GetPaymentUseCase::class,
			[ 'getLegacyPaymentDataObject' => $this->createDefaultLegacyData() ]
		);
	}

	private function createDefaultLegacyData(): LegacyPaymentData {
		// Bogus data
		return new LegacyPaymentData(
			999999,
			999,
			'PPL',
			[
				'paymentValue' => 'almostInfinite',
				'paid' => 'certainly'
			],
		);
	}
}
