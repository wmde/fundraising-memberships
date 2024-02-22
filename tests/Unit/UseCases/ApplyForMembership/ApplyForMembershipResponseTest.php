<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ApplyForMembership;

use LogicException;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicationValidationResult;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipResponse;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipResponse
 */
class ApplyForMembershipResponseTest extends TestCase {

	public function testSuccessResponseReturnsMembershipAndUrl(): void {
		$membership = $this->createStub( MembershipApplication::class );
		$paymentCompletionUrl = 'https://spenden.wikimedia.de/memberships/confirmation';

		$response = ApplyForMembershipResponse::newSuccessResponse( $membership, $paymentCompletionUrl );

		$this->assertTrue( $response->isSuccessful() );
		$this->assertSame( $membership, $response->getMembershipApplication() );
		$this->assertSame( $paymentCompletionUrl, $response->getPaymentCompletionUrl() );
		$this->assertCount( 0, $response->getValidationResult()->getViolations() );
	}

	public function testGivenFailureResponse_isSuccessfulReturnsFalse(): void {
		$response = ApplyForMembershipResponse::newFailureResponse( $this->givenValidationResultWithErrors() );

		$this->assertFalse( $response->isSuccessful() );
	}

	public function testGivenFailureResponse_getMembershipApplicationWillThrowException(): void {
		$response = ApplyForMembershipResponse::newFailureResponse( $this->givenValidationResultWithErrors() );

		$this->expectException( LogicException::class );

		$response->getMembershipApplication();
	}

	public function testGivenFailureResponse_getPaymentCompletionUrlWillThrowException(): void {
		$response = ApplyForMembershipResponse::newFailureResponse( $this->givenValidationResultWithErrors() );

		$this->expectException( LogicException::class );

		$response->getPaymentCompletionUrl();
	}

	private function givenValidationResultWithErrors(): ApplicationValidationResult {
		return new ApplicationValidationResult( [
			ApplicationValidationResult::SOURCE_APPLICANT_CITY => ApplicationValidationResult::VIOLATION_MISSING
		] );
	}
}
