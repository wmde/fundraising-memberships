<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineFeeChangeRepository;
use WMDE\Fundraising\MembershipContext\Domain\FeeChangeException;
use WMDE\Fundraising\MembershipContext\Domain\Model\FeeChange;
use WMDE\Fundraising\MembershipContext\Domain\Model\FeeChangeState;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FeeChanges;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;

#[CoversClass( DoctrineFeeChangeRepository::class )]
class DoctrineFeeChangeRepositoryTest extends TestCase {

	private EntityManager $entityManager;

	protected function setUp(): void {
		$this->entityManager = TestEnvironment::newInstance()->getEntityManager();
	}

	public function testFeeChangeExists(): void {
		$this->insertFeeChanges( FeeChanges::newNewFeeChange( FeeChanges::UUID_1 ) );
		$repository = new DoctrineFeeChangeRepository( $this->entityManager );

		$this->assertTrue( $repository->feeChangeExists( FeeChanges::UUID_1 ) );
		$this->assertFalse( $repository->feeChangeExists( FeeChanges::UUID_2 ) );
	}

	public function testGetsFeeChange(): void {
		$feeChange2 = FeeChanges::newNewFeeChange( FeeChanges::UUID_2 );
		$this->insertFeeChanges( FeeChanges::newNewFeeChange( FeeChanges::UUID_1 ), $feeChange2 );

		$repository = new DoctrineFeeChangeRepository( $this->entityManager );

		$this->assertEquals( $feeChange2, $repository->getFeeChange( FeeChanges::UUID_2 ) );
	}

	public function testThrowsExceptionIfUuidDoesNotExist(): void {
		$this->insertFeeChanges( FeeChanges::newNewFeeChange( FeeChanges::UUID_1 ), FeeChanges::newNewFeeChange( FeeChanges::UUID_2 ) );

		$repository = new DoctrineFeeChangeRepository( $this->entityManager );

		$this->expectException( FeeChangeException::class );
		$repository->getFeeChange( FeeChanges::UUID_3 );
	}

	public function testUpdatesFeeChange(): void {
		$feeChange = FeeChanges::newNewFeeChange( FeeChanges::UUID_1 );
		$this->insertFeeChanges( $feeChange );

		$feeChange->updateMembershipFee( 88, FeeChanges::MEMBER_NAME );
		$exportDate = new \DateTime( FeeChanges::EXPORT_DATE );
		$feeChange->export( $exportDate );

		$repository = new DoctrineFeeChangeRepository( $this->entityManager );
		$repository->storeFeeChange( $feeChange );

		/** @var FeeChange $storedFeeChange */
		$storedFeeChange = $this->entityManager->find( FeeChange::class, 1 );

		$this->assertEquals( 88, $storedFeeChange->getPaymentId() );
		$this->assertEquals( FeeChangeState::EXPORTED, $storedFeeChange->getState() );
		$this->assertEquals( $exportDate, $storedFeeChange->getExportDate() );
	}

	public function insertFeeChanges( FeeChange ...$feeChanges ): void {
		foreach ( $feeChanges as $feeChange ) {
			$this->entityManager->persist( $feeChange );
		}
		$this->entityManager->flush();
	}
}
