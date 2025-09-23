<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Domain\Model\FeeChange;
use WMDE\Fundraising\MembershipContext\Domain\Model\FeeChangeState;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FeeChanges;

#[CoversClass( FeeChange::class )]
class FeeChangeTest extends TestCase {
	public function testUpgradeMembershipUpgradesMembership(): void {
		$feeChange = FeeChanges::newNewFeeChange( FeeChanges::UUID_1 );

		$feeChange->updateMembershipFee( 12, FeeChanges::MEMBER_NAME );

		$this->assertEquals( 12, $feeChange->getPaymentId() );
		$this->assertEquals( FeeChanges::MEMBER_NAME, $feeChange->getMemberName() );
		$this->assertEquals( FeeChangeState::FILLED, $feeChange->getState() );
	}

	public function testExportExports(): void {
		$exportDate = new \DateTime( FeeChanges::EXPORT_DATE );
		$feeChange = FeeChanges::newNewFeeChange( FeeChanges::UUID_1 );

		$feeChange->export( $exportDate );

		$this->assertEquals( $exportDate, $feeChange->getExportDate() );
		$this->assertEquals( FeeChangeState::EXPORTED, $feeChange->getState() );
	}
}
