<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ValidateMembershipFee;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee\ValidateFeeRequest;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee\ValidateFeeRequest
 */
class ValidateFeeRequestTest extends TestCase {
	public function testAccessors(): void {
		$request = ValidateFeeRequest::newInstance()
			->withApplicantType( ValidateFeeRequest::PERSON_APPLICANT )
			->withFee( Euro::newFromInt( 42 ) )
			->withInterval( 12 );

		$this->assertSame( ValidateFeeRequest::PERSON_APPLICANT, $request->getApplicantType() );
		$this->assertEquals( Euro::newFromInt( 42 ), $request->getMembershipFee() );
		$this->assertSame( 12, $request->getPaymentIntervalInMonths() );
	}

	public function testApplicantTypeIsTrimmed(): void {
		$request = ValidateFeeRequest::newInstance()
			->withApplicantType( sprintf( "\t   %s \n", ValidateFeeRequest::COMPANY_APPLICANT ) );

		$this->assertSame( ValidateFeeRequest::COMPANY_APPLICANT, $request->getApplicantType() );
	}

}
