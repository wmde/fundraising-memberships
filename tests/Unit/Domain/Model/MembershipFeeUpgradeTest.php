<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipFeeUpgrade;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;

#[CoversClass( MembershipFeeUpgrade::class )]
class MembershipFeeUpgradeTest extends TestCase {

	public function testSetAmountAndInterval(): void {
		$membershipFeeUpgrade = new MembershipFeeUpgrade(1, 'test@gmail.com', 'd9a51446-8b26-4910-85b1-9eab4e0113f9');

		$membershipFeeUpgrade->updateMembershipFee( PaymentInterval::Monthly, 10 );

		$this->assertSame( 10, $membershipFeeUpgrade->getAmount() );
		$this->assertSame( PaymentInterval::Monthly, $membershipFeeUpgrade->getInterval() );
	}
}
