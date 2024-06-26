<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ValidateMembershipFee;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Domain\MembershipPaymentValidator;
use WMDE\Fundraising\MembershipContext\Infrastructure\PaymentServiceFactory;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicantType;
use WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee\ValidateMembershipFeeUseCase;
use WMDE\Fundraising\PaymentContext\Domain\PaymentType;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\CreatePaymentUseCase;

#[CoversClass( ValidateMembershipFeeUseCase::class )]
class ValidateMembershipFeeUseCaseTest extends TestCase {

	public function testGivenValidRequest_validationSucceeds(): void {
		$response = $this->newUseCase()->validate(
			12,
			3,
			"person",
			"BEZ"
		);

		$this->assertTrue( $response->isSuccessful() );
		$this->assertCount( 0, $response->getValidationErrors() );
	}

	private function newUseCase(): ValidateMembershipFeeUseCase {
		return new ValidateMembershipFeeUseCase( new PaymentServiceFactory(
			$this->createStub( CreatePaymentUseCase::class ),
			[ PaymentType::DirectDebit ]
		) );
	}

	#[DataProvider( 'invalidAmountProvider' )]
	public function testGivenInvalidAmount_validationFails( int $amount, int $intervalInMonths, string $expectedError ): void {
		$response = $this->newUseCase()->validate( $amount, $intervalInMonths, ApplicantType::PERSON_APPLICANT->value, "BEZ" );

		$this->assertFalse( $response->isSuccessful() );
		$constraintViolation = $response->getValidationErrors()[0];
		$this->assertEquals( $expectedError, $constraintViolation->getMessageIdentifier() );
	}

	/**
	 * @return iterable<array{ int, int, string }>
	 */
	public static function invalidAmountProvider(): iterable {
		yield 'too low single payment' => [ 1, 12, MembershipPaymentValidator::FEE_TOO_LOW ];
		yield 'just too low single payment' => [ 23, 12, MembershipPaymentValidator::FEE_TOO_LOW ];

		yield 'too low 12 times' => [ 1, 1, MembershipPaymentValidator::FEE_TOO_LOW ];
		yield 'too low 4 times' => [ 5, 3, MembershipPaymentValidator::FEE_TOO_LOW ];
	}

	#[DataProvider( 'validAmountProvider' )]
	public function testGivenValidAmount_validationSucceeds( int $amount, int $intervalInMonths ): void {
		$this->assertTrue(
			$this->newUseCase()
				->validate( $amount, $intervalInMonths, ApplicantType::PERSON_APPLICANT->value, "BEZ" )
				->isSuccessful()
		);
	}

	/**
	 * @return iterable<string, array<int, int>>
	 */
	public static function validAmountProvider(): iterable {
		yield 'single payment' => [ 50, 12 ];
		yield 'just enough single payment' => [ 24, 12 ];
		yield 'high single payment' => [ 31333, 12 ];

		yield 'just enough 12 times' => [ 2, 1 ];
		yield 'just enough 4 times' => [ 6, 3 ];
	}

	public function testGivenValidCompanyAmount_validationSucceeds(): void {
		$this->assertTrue(
			$this->newUseCase()
				->validate( 100, 12, ApplicantType::COMPANY_APPLICANT->value, "BEZ" )
				->isSuccessful()
		);
	}

	public function testGivenInvalidCompanyAmount_validationFails(): void {
		$this->assertFalse(
			$this->newUseCase()
				->validate(
					99, 12, ApplicantType::COMPANY_APPLICANT->value, "BEZ" )
				->isSuccessful()
		);
	}

	public function testGivenLargeInteger_validationFailsWhenConstructingEuroClass(): void {
		$fakePaymentValidator = $this->createMock( MembershipPaymentValidator::class );
		$fakePaymentValidator->expects( $this->never() )->method( $this->anything() );
		$stubbedFactory = $this->createStub( PaymentServiceFactory::class );
		$stubbedFactory->method( 'newPaymentValidator' )->willReturn( $fakePaymentValidator );
		$useCase = new ValidateMembershipFeeUseCase( $stubbedFactory );

		$result = $useCase->validate( PHP_INT_MAX, 12, ApplicantType::PERSON_APPLICANT->value, "BEZ" );

		$this->assertFalse( $result->isSuccessful() );
		$this->assertSame( MembershipPaymentValidator::FEE_TOO_HIGH, $result->getValidationErrors()[0]->getMessageIdentifier() );
	}

	public function testGivenNegativeInteger_validationFailsWhenConstructingEuroClass(): void {
		$fakePaymentValidator = $this->createMock( MembershipPaymentValidator::class );
		$fakePaymentValidator->expects( $this->never() )->method( $this->anything() );
		$stubbedFactory = $this->createStub( PaymentServiceFactory::class );
		$stubbedFactory->method( 'newPaymentValidator' )->willReturn( $fakePaymentValidator );
		$useCase = new ValidateMembershipFeeUseCase( $stubbedFactory );

		$result = $useCase->validate( -5, 12, ApplicantType::PERSON_APPLICANT->value, "BEZ" );

		$this->assertFalse( $result->isSuccessful() );
		$this->assertNotSame( MembershipPaymentValidator::FEE_TOO_HIGH, $result->getValidationErrors()[0]->getMessageIdentifier() );
	}

	public function testGivenInvalidInterval_zero_validationFails(): void {
		$useCase = $this->newUseCase();

		$response = $useCase->validate( 12, 0, ApplicantType::PERSON_APPLICANT->value, "BEZ" );

		$constraintViolation = $response->getValidationErrors()[0];
		$this->assertSame( MembershipPaymentValidator::INVALID_INTERVAL, $constraintViolation->getMessageIdentifier() );
	}

	public function testGivenInvalidInterval_negative_validationFails(): void {
		$useCase = $this->newUseCase();

		$response = $useCase->validate( 12, -1, ApplicantType::PERSON_APPLICANT->value, "BEZ" );

		$constraintViolation = $response->getValidationErrors()[0];
		$this->assertSame( 'Invalid Interval', $constraintViolation->getMessageIdentifier() );
	}

	public function testGivenInvalidApplicantType_validationFails(): void {
		$useCase = $this->newUseCase();

		$response = $useCase->validate( 12, 3, "bogus", "BEZ" );

		$constraintViolation = $response->getValidationErrors()[0];
		$this->assertSame( 'invalid-applicant-type', $constraintViolation->getMessageIdentifier() );
	}

	public function testGivenInvalidPaymentType_validationFails(): void {
		$useCase = $this->newUseCase();

		$response = $useCase->validate( 12, 3, ApplicantType::PERSON_APPLICANT->value, "PPL" );

		$constraintViolation = $response->getValidationErrors()[0];
		$this->assertSame( 'invalid_payment_type', $constraintViolation->getMessageIdentifier() );
	}

}
