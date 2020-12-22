<?php

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;

/**
 * @covers \WMDE\Fundraising\MembershipContext\Domain\Model\Incentive
 */
class IncentiveTest extends TestCase {
	public function testIdIsNullForNewInstances(): void {
		$incentive = new Incentive( 'a_pony' );

		$this->assertNull( $incentive->getId() );
	}

	public function testConstructorSetsName(): void {
		$incentive = new Incentive( 'good_karma' );

		$this->assertSame( 'good_karma', $incentive->getName() );
	}
}
