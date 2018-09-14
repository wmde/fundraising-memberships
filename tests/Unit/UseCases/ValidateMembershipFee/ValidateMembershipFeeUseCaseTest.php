<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ValidateMembershipFee;

use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicationValidationResult as Result;
use WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee\ValidateFeeRequest;
use WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee\ValidateFeeResult;
use WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee\ValidateMembershipFeeUseCase;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee\ValidateMembershipFeeUseCase
 *
 * @license GNU GPL v2+
 */
class ValidateMembershipFeeUseCaseTest extends \PHPUnit\Framework\TestCase {

	public function testGivenValidRequest_validationSucceeds(): void {
		$response = $this->newUseCase()->validate(
			ValidateFeeRequest::newInstance()
				->withFee( '12.34' )
				->withApplicantType( ValidateFeeRequest::PERON_APPLICANT )
				->withInterval( 3 )
		);

		$this->assertTrue( $response->isSuccessful() );
		$this->assertNull( $response->getErrorCode() );
	}

	private function newUseCase(): ValidateMembershipFeeUseCase {
		return new ValidateMembershipFeeUseCase();
	}

	/**
	 * @dataProvider invalidAmountProvider
	 */
	public function testGivenInvalidAmount_validationFails( string $amount, int $intervalInMonths, string $expectedError ): void {
		$response = $this->newUseCase()->validate(
			ValidateFeeRequest::newInstance()
				->withFee( $amount )
				->withApplicantType( ValidateFeeRequest::PERON_APPLICANT )
				->withInterval( $intervalInMonths )
		);

		$this->assertFalse( $response->isSuccessful() );
		$this->assertSame( $expectedError, $response->getErrorCode() );
	}

	public function invalidAmountProvider(): iterable {
		yield 'invalid: negative' => [ '-1.00', 3, ValidateFeeResult::ERROR_NOT_MONEY ];
		yield 'invalid' => [ 'y u no btc', 3, ValidateFeeResult::ERROR_NOT_MONEY ];

		yield 'too low single payment' => [ '1.00', 12, ValidateFeeResult::ERROR_TOO_LOW ];
		yield 'just too low single payment' => [ '23.99', 12, ValidateFeeResult::ERROR_TOO_LOW ];
		yield 'max too low single payment' => [ '0', 12, ValidateFeeResult::ERROR_TOO_LOW ];

		yield 'too low 12 times' => [ '1.99', 1, ValidateFeeResult::ERROR_TOO_LOW ];
		yield 'too low 4 times' => [ '5.99', 3, ValidateFeeResult::ERROR_TOO_LOW ];
	}

	/**
	 * @dataProvider validAmountProvider
	 */
	public function testGivenValidAmount_validationSucceeds( string $amount, int $intervalInMonths ): void {
		$this->assertTrue(
			$this->newUseCase()
				->validate(
					ValidateFeeRequest::newInstance()
						->withFee( $amount )
						->withApplicantType( 'person' )
						->withInterval( $intervalInMonths )
				)
				->isSuccessful()
		);
	}

	public function validAmountProvider(): iterable {
		yield 'single payment' => [ '50.00', 12 ];
		yield 'just enough single payment' => [ '24.00', 12 ];
		yield 'high single payment' => [ '31333.37', 12 ];

		yield 'just enough 12 times' => [ '2.00', 1 ];
		yield 'just enough 4 times' => [ '6.00', 3 ];
	}

	public function testGivenValidCompanyAmount_validationSucceeds(): void {
		$this->assertTrue(
			$this->newUseCase()
				->validate(
					ValidateFeeRequest::newInstance()
						->withFee( '100.00' )
						->withApplicantType( ValidateFeeRequest::COMPANY_APPLICANT )
						->withInterval( 12 )
				)
				->isSuccessful()
		);
	}

	public function testGivenInvalidCompanyAmount_validationFails(): void {
		$this->assertFalse(
			$this->newUseCase()
				->validate(
					ValidateFeeRequest::newInstance()
						->withFee( '99.99' )
						->withApplicantType( ValidateFeeRequest::COMPANY_APPLICANT )
						->withInterval( 12 )
				)
				->isSuccessful()
		);
	}

}
