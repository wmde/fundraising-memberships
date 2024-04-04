<?php

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\CancelMembershipApplication;

use LogicException;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication\CancellationRequest;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication\CancellationRequest
 */
class CancellationRequestTest extends TestCase {

	private const AUTH_USER_NAME = "Pintman Paddy Losty";

	public function testAuthorizedRequestContainsApplicationIdAndUsername(): void {
		$request = new CancellationRequest( 42, self::AUTH_USER_NAME );

		$this->assertSame( 42, $request->getApplicationId() );
		$this->assertSame( self::AUTH_USER_NAME, $request->getUserName() );
	}

	public function testRequestWithUserIsAuthorized(): void {
		$request = new CancellationRequest( 42, self::AUTH_USER_NAME );

		$this->assertTrue( $request->initiatedByApplicant() );
	}

	public function testOnGetUnauthorizedRequestUserName_throwsException(): void {
		$request = new CancellationRequest( 42 );

		$this->expectException( LogicException::class );
		$request->getUserName();
	}
}
