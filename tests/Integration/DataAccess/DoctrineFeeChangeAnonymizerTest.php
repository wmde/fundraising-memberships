<?php

declare( strict_types = 1 );

namespace Integration\DataAccess;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineFeeChangeAnonymizer;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineFeeChangeRepository;
use WMDE\Fundraising\MembershipContext\Domain\AnonymizationException;
use WMDE\Fundraising\MembershipContext\Domain\FeeChangeException;
use WMDE\Fundraising\MembershipContext\Domain\Model\FeeChangeState;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FakePaymentAnonymizer;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FeeChanges;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentAnonymizer;

#[CoversClass( DoctrineFeeChangeAnonymizer::class )]
class DoctrineFeeChangeAnonymizerTest extends TestCase {

	private Connection $conn;
	private EntityManager $entityManager;
	private DoctrineFeeChangeRepository $repository;

	public function setUp(): void {
		$factory = TestEnvironment::newInstance()->getFactory();
		$this->conn = $factory->getConnection();
		$this->entityManager = $factory->getEntityManager();
		$this->repository = new DoctrineFeeChangeRepository( $this->entityManager );
		$this->insertFeeChanges();
	}

	private function newDoctrineFeeChangeAnonymizer(
		?DoctrineFeeChangeRepository $repository = null,
		?PaymentAnonymizer $paymentAnonymizer = null
	): DoctrineFeeChangeAnonymizer {
		return new DoctrineFeeChangeAnonymizer(
			$repository ?? $this->repository,
			$this->entityManager,
				$paymentAnonymizer ?? new FakePaymentAnonymizer(),
		);
	}

	public function testAnonymizeWithIdsCleansUpFields(): void {
		$anonymizer = $this->newDoctrineFeeChangeAnonymizer();

		$anonymizer->anonymizeWithIds( FeeChanges::UUID_1, FeeChanges::UUID_3 );

		$this->assertFeeChangeIsAnonymised( FeeChanges::UUID_1 );
		$this->assertFeeChangeIsAnonymised( FeeChanges::UUID_3 );
	}

	public function testAnonymizeWithIdsAnonymizesPayments(): void {
		$paymentAnonymizer = new FakePaymentAnonymizer();
		$anonymizer = $this->newDoctrineFeeChangeAnonymizer( paymentAnonymizer: $paymentAnonymizer );

		$anonymizer->anonymizeWithIds( FeeChanges::UUID_2, FeeChanges::UUID_3 );

		$this->assertSame( [ FeeChanges::PAYMENT_ID, FeeChanges::PAYMENT_ID ], $paymentAnonymizer->paymentIds );
	}

	public function testAnonymizeWithIdsThrowsExceptionWhenIdDoesNotExist(): void {
		$anonymizer = $this->newDoctrineFeeChangeAnonymizer();

		$this->expectException( AnonymizationException::class );

		$anonymizer->anonymizeWithIds( 'I am not a real UUID' );
	}

	public function testAnonymizeWithIdsTransformsFeeChangeExceptions(): void {
		$feeChangeRepository = $this->createStub( DoctrineFeeChangeRepository::class );
		$feeChangeRepository->method( 'getFeeChange' )->willThrowException( new FeeChangeException( 'Sorry bud, no go' ) );

		$anonymizer = $this->newDoctrineFeeChangeAnonymizer( repository: $feeChangeRepository );

		$this->expectException( AnonymizationException::class );

		$anonymizer->anonymizeWithIds( FeeChanges::UUID_1 );
	}

	public function testAnonymizeAllAnonymizesExportedFeeChangesOnly(): void {
		$anonymizer = $this->newDoctrineFeeChangeAnonymizer();

		$anonymizer->anonymizeAll();

		$this->assertFeeChangeIsAnonymised( FeeChanges::UUID_1 );
		$this->assertFeeChangeIsAnonymised( FeeChanges::UUID_2 );
		$this->assertFeeChangeIsFilled( FeeChanges::UUID_3 );
		$this->assertFeeChangeIsNew( FeeChanges::UUID_4 );
	}

	public function testAnonymizeAllAnonymizesPayments(): void {
		$paymentAnonymizer = new FakePaymentAnonymizer();
		$anonymizer = $this->newDoctrineFeeChangeAnonymizer( paymentAnonymizer: $paymentAnonymizer );

		$anonymizer->anonymizeAll();

		$this->assertSame( [ FeeChanges::PAYMENT_ID, FeeChanges::PAYMENT_ID ], $paymentAnonymizer->paymentIds );
	}

	private function insertFeeChanges(): void {
		$this->repository->storeFeeChange( FeeChanges::newExportedFeeChange( FeeChanges::UUID_1 ) );
		$this->repository->storeFeeChange( FeeChanges::newExportedFeeChange( FeeChanges::UUID_2 ) );
		$this->repository->storeFeeChange( FeeChanges::newFilledFeeChange( FeeChanges::UUID_3 ) );
		$this->repository->storeFeeChange( FeeChanges::newNewFeeChange( FeeChanges::UUID_4 ) );
	}

	private function assertFeeChangeIsAnonymised( string $uuid ): void {
		$row = $this->getFeeChange( $uuid );

		$this->assertSame( '', $row[ 'member_name' ] );
		$this->assertEquals( FeeChangeState::NEW->value, $row[ 'state' ] );
		$this->assertArrayHasKey( 'export_date', $row );
		$this->assertNull( $row[ 'export_date' ] );
		$this->assertArrayHasKey( 'filled_on', $row );
		$this->assertNull( $row[ 'filled_on' ] );
	}

	private function assertFeeChangeIsNew( string $uuid ): void {
		$row = $this->getFeeChange( $uuid );

		$this->assertSame( '', $row[ 'member_name' ] );
		$this->assertEquals( FeeChangeState::NEW->value, $row[ 'state' ] );
		$this->assertArrayHasKey( 'export_date', $row );
		$this->assertNull( $row[ 'export_date' ] );
		$this->assertArrayHasKey( 'filled_on', $row );
		$this->assertNull( $row[ 'filled_on' ] );
	}

	private function assertFeeChangeIsFilled( string $uuid ): void {
		$row = $this->getFeeChange( $uuid );

		$this->assertSame( FeeChanges::MEMBER_NAME, $row[ 'member_name' ] );
		$this->assertEquals( FeeChangeState::FILLED->value, $row[ 'state' ] );
		$this->assertArrayHasKey( 'export_date', $row );
		$this->assertNull( $row[ 'export_date' ] );
		$this->assertSame( FeeChanges::FILLED_ON_DATE, $row[ 'filled_on' ] );
	}

	/**
	 * @param string $uuid
	 *
	 * @return array<string, mixed>
	 * @throws \Doctrine\DBAL\Exception
	 */
	private function getFeeChange( string $uuid ): array {
		/** @var array<string, mixed> $result */
		$result = $this->conn->executeQuery(
			"SELECT * FROM membership_fee_changes WHERE uuid = :uuid",
			[ 'uuid' => $uuid ]
		)->fetchAssociative();

		return $result;
	}

}
