<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ApplyForMembership;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplicationRequest;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\SucceedingEmailValidator;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicationValidationResult as Result;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipRequest;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\MembershipApplicationValidator;
use WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee\ValidateFeeResult;
use WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee\ValidateMembershipFeeUseCase;
use WMDE\Fundraising\PaymentContext\Domain\BankDataValidationResult as BankResult;
use WMDE\Fundraising\PaymentContext\Domain\BankDataValidator;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlocklist;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;
use WMDE\FunValidators\Validators\EmailValidator;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\MembershipApplicationValidator
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicationValidationResult
 *
 * @license GPL-2.0-or-later
 */
class MembershipApplicationValidatorTest extends TestCase {

	private const BLOCKED_IBAN = 'LU761111000872960000';

	private ValidateMembershipFeeUseCase $feeValidator;

	private BankDataValidator $bankDataValidator;

	private EmailValidator $emailValidator;

	private IbanBlocklist $ibanBlockList;

	public function setUp(): void {
		// TODO: When payment refactoring is implemented re-activate this method
		// phpcs:disable Squiz.PHP.NonExecutableCode.Unreachable
		return;
		$this->feeValidator = $this->newSucceedingFeeValidator();
		$this->bankDataValidator = $this->newSucceedingBankDataValidator();
		$this->ibanBlockList = $this->newEmptyIbanBlocklist();
		$this->emailValidator = new SucceedingEmailValidator();
	}

	public function testGivenValidRequest_validationSucceeds(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$validRequest = $this->newValidRequest();
		$response = $this->newValidator()->validate( $validRequest );

		$this->assertEquals( new Result(), $response );
		$this->assertCount( 0, $response->getViolationSources() );
		$this->assertTrue( $response->isSuccessful() );
	}

	private function newValidator(): MembershipApplicationValidator {
		return new MembershipApplicationValidator(
			$this->feeValidator,
			$this->bankDataValidator,
			$this->ibanBlockList,
			$this->emailValidator
		);
	}

	/**
	 * @dataProvider failingFeeResults
	 */
	public function testWhenFeeValidationFails_overallValidationAlsoFails( ValidateFeeResult $failingResult, Result $expectedResult ): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$this->feeValidator = $this->failingFeeValidator( $failingResult );

		$response = $this->newValidator()->validate( $this->newValidRequest() );

		$this->assertEquals( $expectedResult, $response );
	}

	private function newEmptyIbanBlocklist(): IbanBlocklist {
		return new IbanBlocklist( [] );
	}

	private function failingFeeValidator( ValidateFeeResult $failingResult ): ValidateMembershipFeeUseCase {
		$feeValidator = $this->createMock( ValidateMembershipFeeUseCase::class );

		$feeValidator->method( 'validate' )
			->willReturn( $failingResult );

		return $feeValidator;
	}

	public function failingFeeResults(): iterable {
		yield [
			ValidateFeeResult::newTooLowResponse(),
			new Result( [ Result::SOURCE_PAYMENT_AMOUNT => Result::VIOLATION_TOO_LOW ] )
		];
		yield [
			ValidateFeeResult::newIntervalInvalidResponse(),
			new Result( [ Result::SOURCE_INTERVAL => Result::VIOLATION_INVALID_INTERVAL ] )
		];
	}

	private function newSucceedingFeeValidator(): ValidateMembershipFeeUseCase {
		$feeValidator = $this->createMock( ValidateMembershipFeeUseCase::class );

		$feeValidator->method( 'validate' )
			->willReturn( ValidateFeeResult::newSuccessResponse() );

		return $feeValidator;
	}

	private function newValidRequest(): ApplyForMembershipRequest {
		return ValidMembershipApplicationRequest::newValidRequest();
	}

	private function newSucceedingBankDataValidator(): BankDataValidator {
		$feeValidator = $this->getMockBuilder( BankDataValidator::class )
			->disableOriginalConstructor()->getMock();

		$feeValidator->method( 'validate' )
			->willReturn( new ValidationResult() );

		return $feeValidator;
	}

	public function testWhenBankDataValidationFails_constraintViolationValuesArePropagated(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$this->bankDataValidator = $this->createMock( BankDataValidator::class );
		$this->bankDataValidator->method( 'validate' )->willReturn(
			new ValidationResult(
				new ConstraintViolation( '', BankResult::VIOLATION_MISSING, BankResult::SOURCE_IBAN ),
				new ConstraintViolation( 'ABC', BankResult::VIOLATION_INVALID_BIC, BankResult::SOURCE_BIC )
			)
		);

		$request = $this->newValidRequest();

		$this->assertRequestValidationResultInErrors(
			$request,
			[
				BankResult::SOURCE_IBAN => BankResult::VIOLATION_MISSING,
				BankResult::SOURCE_BIC => BankResult::VIOLATION_INVALID_BIC
			]
		);
	}

	public function testWhenIbanIsBlocked_validationFails(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$this->ibanBlockList = new IbanBlocklist( [ self::BLOCKED_IBAN ] );

		$request = $this->newValidRequest();
		$request->getBankData()->setIban( new Iban( self::BLOCKED_IBAN ) );

		$this->assertRequestValidationResultInErrors(
			$request,
			[
				BankResult::SOURCE_IBAN => Result::VIOLATION_IBAN_BLOCKED,
			]
		);
	}

	private function assertRequestValidationResultInErrors( ApplyForMembershipRequest $request, array $expectedErrors ): void {
		$this->assertEquals(
			new Result( $expectedErrors ),
			$this->newValidator()->validate( $request )
		);
	}

	public function testWhenDateOfBirthIsNotDate_validationFails(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$request = $this->newValidRequest();
		$request->setApplicantDateOfBirth( 'this is not a valid date' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_DATE_OF_BIRTH => Result::VIOLATION_NOT_DATE ]
		);
	}

	/**
	 * @dataProvider invalidPhoneNumberProvider
	 */
	public function testWhenApplicantPhoneNumberIsInvalid_validationFails( string $invalidPhoneNumber ): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$request = $this->newValidRequest();
		$request->setApplicantPhoneNumber( $invalidPhoneNumber );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_PHONE_NUMBER => Result::VIOLATION_NOT_PHONE_NUMBER ]
		);
	}

	public function invalidPhoneNumberProvider(): array {
		return [
			'potato' => [ 'potato' ],

			// TODO: we use the regex from the old app, which allows for lots of bugus. Improve when time
//			'number plus stuff' => [ '01189998819991197253 (invalid edition)' ],
		];
	}

	/**
	 * @dataProvider emailViolationTypeProvider
	 */
	public function testWhenApplicantEmailIsInvalid_validationFails( string $emailViolationType ): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$this->emailValidator = $this->newFailingEmailValidator( $emailViolationType );

		$request = $this->newValidRequest();
		$request->setApplicantEmailAddress( 'this is not a valid email' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_EMAIL => Result::VIOLATION_NOT_EMAIL ]
		);
	}

	public function emailViolationTypeProvider(): array {
		return [
			[ 'email_address_wrong_format' ],
			[ 'email_address_invalid' ],
			[ 'email_address_domain_record_not_found' ],
		];
	}

	private function newFailingEmailValidator( string $violationType ): EmailValidator {
		$feeValidator = $this->getMockBuilder( EmailValidator::class )
			->disableOriginalConstructor()->getMock();

		$feeValidator->method( 'validate' )
			->willReturn( new ValidationResult( new ConstraintViolation( 'this is not a valid email', $violationType ) ) );

		return $feeValidator;
	}

	public function testWhenCompanyIsMissingFromCompanyApplication_validationFails(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$request = $this->newValidRequest();
		$request->markApplicantAsCompany();
		$request->setApplicantCompanyName( '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_COMPANY => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenFirstNameIsMissingFromPersonalApplication_validationFails(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$request = $this->newValidRequest();
		$request->setApplicantFirstName( '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_FIRST_NAME => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenLastNameIsMissingFromPersonalApplication_validationFails(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$request = $this->newValidRequest();
		$request->setApplicantLastName( '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_LAST_NAME => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenSalutationIsMissingFromPersonalApplication_validationFails(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$request = $this->newValidRequest();
		$request->setApplicantSalutation( '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_SALUTATION => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenStreetAddressIsMissing_validationFails(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$request = $this->newValidRequest();
		$request->setApplicantStreetAddress( '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_STREET_ADDRESS => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenPostalCodeIsMissing_validationFails(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$request = $this->newValidRequest();
		$request->setApplicantPostalCode( '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_POSTAL_CODE => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenCityIsMissing_validationFails(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$request = $this->newValidRequest();
		$request->setApplicantCity( '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_CITY => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenCountryCodeIsMissing_validationFails(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$request = $this->newValidRequest();
		$request->setApplicantCountryCode( '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_COUNTRY => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenMembershipTypeIsMissing_validationFails(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$request = $this->newValidRequest();
		$request->setMembershipType( '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_MEMBERSHIP_TYPE => Result::VIOLATION_INVALID_MEMBERSHIP_TYPE ]
		);
	}

	public function testPhoneNumberIsNotProvided_validationDoesNotFail(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$request = $this->newValidRequest();
		$request->setApplicantPhoneNumber( '' );

		$this->assertTrue( $this->newValidator()->validate( $request )->isSuccessful() );
	}

	public function testDateOfBirthIsNotProvided_validationDoesNotFail(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$request = $this->newValidRequest();
		$request->setApplicantDateOfBirth( '' );

		$this->assertTrue( $this->newValidator()->validate( $request )->isSuccessful() );
	}

	public function testPersonalInfoWithLongFields_validationFails(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$longText = str_repeat( 'Cats ', 500 );
		$request = $this->newValidRequest();
		$request->setApplicantFirstName( $longText );
		$request->setApplicantLastName( $longText );
		$request->setApplicantTitle( $longText );
		$request->setApplicantSalutation( $longText );
		$request->setApplicantStreetAddress( $longText );
		$request->setApplicantPostalCode( $longText );
		$request->setApplicantCity( $longText );
		$request->setApplicantCountryCode( $longText );
		$this->assertRequestValidationResultInErrors(
			$request,
			[
				Result::SOURCE_APPLICANT_FIRST_NAME => Result::VIOLATION_WRONG_LENGTH,
				Result::SOURCE_APPLICANT_LAST_NAME => Result::VIOLATION_WRONG_LENGTH,
				Result::SOURCE_APPLICANT_SALUTATION => Result::VIOLATION_WRONG_LENGTH,
				Result::SOURCE_APPLICANT_STREET_ADDRESS => Result::VIOLATION_WRONG_LENGTH,
				Result::SOURCE_APPLICANT_POSTAL_CODE => Result::VIOLATION_WRONG_LENGTH,
				Result::SOURCE_APPLICANT_CITY => Result::VIOLATION_WRONG_LENGTH,
				Result::SOURCE_APPLICANT_COUNTRY => Result::VIOLATION_WRONG_LENGTH
			]
		);
	}

	public function testContactInfoWithLongFields_validationFails(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$request = $this->newValidRequest();
		$request->setApplicantEmailAddress( str_repeat( 'Cats', 500 ) . '@example.com' );
		$request->setApplicantPhoneNumber( str_repeat( '1234', 500 ) );

		$this->assertRequestValidationResultInErrors(
			$request,
			[
				Result::SOURCE_APPLICANT_EMAIL => Result::VIOLATION_WRONG_LENGTH,
				Result::SOURCE_APPLICANT_PHONE_NUMBER => Result::VIOLATION_WRONG_LENGTH
			]
		);
	}

	public function testGivenValidRequestUsingPayPal_validationSucceeds(): void {
		$this->markTestIncomplete( 'This will work when we update the use case' );
		$validRequest = $this->newValidRequestUsingPayPal();
		$response = $this->newValidator()->validate( $validRequest );

		$this->assertEquals( new Result(), $response );
		$this->assertCount( 0, $response->getViolationSources() );
		$this->assertTrue( $response->isSuccessful() );
	}

	private function newValidRequestUsingPayPal(): ApplyForMembershipRequest {
		$request = ValidMembershipApplicationRequest::newValidRequest();
		$request->setPaymentType( ValidMembershipApplication::PAYMENT_TYPE_PAYPAL );
		$request->setBankData( new BankData() );
		return $request;
	}

}
