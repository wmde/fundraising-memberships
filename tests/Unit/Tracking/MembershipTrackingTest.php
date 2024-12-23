<?php

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\Tracking;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipTracking;

#[CoversClass( MembershipTracking::class )]
class MembershipTrackingTest extends TestCase {
	public function testConstructorInitializesProperties(): void {
		$trackingInfo = new MembershipTracking(
			campaignCode: '07-ba-20241028',
			keyword: 'org-07-2411028-ctrl'
		);

		$this->assertSame( '07-ba-20241028', $trackingInfo->getCampaignCode() );
		$this->assertSame( 'org-07-2411028-ctrl', $trackingInfo->getKeyword() );
	}

	#[DataProvider( 'matomoStringProvider' )]
	public function testGetMatomoString( string $campaignCode, string $keyword, string $expectedMatomoString ): void {
		$trackingInfo = new MembershipTracking( $campaignCode, $keyword );

		$this->assertSame( $expectedMatomoString, $trackingInfo->getMatomoString() );
	}

	/**
	 * @return iterable<string,array{string,string,string}>
	 */
	public static function matomoStringProvider(): iterable {
		yield 'complete data' => [ '13-ba-20241125', 'org-13-20241125-var', '13-ba-20241125/org-13-20241125-var' ];
		yield 'empty keyword' => [ '13-ba-20241125', '', '13-ba-20241125/' ];
		yield 'empty campaign' => [ '', 'org-13-20241125-var', '/org-13-20241125-var' ];
		yield 'missing data' => [ '', '', '' ];
	}

}
