<?php

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;

#[CoversClass( Incentive::class )]
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
