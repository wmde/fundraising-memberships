<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ApplyForMembership;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\SucceedingEmailValidator;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipApplicationTrackingInfo;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicationValidationResult as Result;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplyForMembershipRequest;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\MembershipApplicationValidator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentType;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentParameters;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResult;
use WMDE\FunValidators\Validators\EmailValidator;

/**
 * @covers \WMDE\FunValidators\ValidationResult
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\MembershipApplicationValidator
 */
class MembershipApplicationValidatorTest extends TestCase {

	private EmailValidator $emailValidator;

	public function setUp(): void {
		$this->emailValidator = new SucceedingEmailValidator();
	}

	public function testGivenValidRequest_validationSucceeds(): void {
		$validRequest = $this->newPrivateRequest();
		$response = $this->newValidator()->validate( $validRequest );

		$this->assertEquals( new Result(), $response );
		$this->assertCount( 0, $response->getViolationSources() );
		$this->assertTrue( $response->isSuccessful() );
	}

	private function newValidator(): MembershipApplicationValidator {
		return new MembershipApplicationValidator(
			$this->emailValidator
		);
	}

	private function newPrivateRequest(
		string $membershipType = ValidMembershipApplication::MEMBERSHIP_TYPE,
		string $applicantSalutation = ValidMembershipApplication::APPLICANT_SALUTATION,
		string $applicantTitle = ValidMembershipApplication::APPLICANT_TITLE,
		string $applicantFirstName = ValidMembershipApplication::APPLICANT_FIRST_NAME,
		string $applicantLastName = ValidMembershipApplication::APPLICANT_LAST_NAME,
		string $applicantStreetAddress = ValidMembershipApplication::APPLICANT_STREET_ADDRESS,
		string $applicantPostalCode = ValidMembershipApplication::APPLICANT_POSTAL_CODE,
		string $applicantCity = ValidMembershipApplication::APPLICANT_CITY,
		string $applicantCountryCode = ValidMembershipApplication::APPLICANT_COUNTRY_CODE,
		string $applicantEmailAddress = ValidMembershipApplication::APPLICANT_EMAIL_ADDRESS,
		string $applicantPhoneNumber = ValidMembershipApplication::APPLICANT_PHONE_NUMBER,
		?PaymentParameters $paymentParameters = null,
		string $applicantDateOfBirth = ValidMembershipApplication::APPLICANT_DATE_OF_BIRTH,
	): ApplyForMembershipRequest {
		return ApplyForMembershipRequest::newPrivateApplyForMembershipRequest(
			membershipType: $membershipType,
			applicantSalutation: $applicantSalutation,
			applicantTitle: $applicantTitle,
			applicantFirstName: $applicantFirstName,
			applicantLastName: $applicantLastName,
			applicantStreetAddress: $applicantStreetAddress,
			applicantPostalCode: $applicantPostalCode,
			applicantCity: $applicantCity,
			applicantCountryCode: $applicantCountryCode,
			applicantEmailAddress: $applicantEmailAddress,
			optsIntoDonationReceipt: false,
			incentives: [],
			paymentParameters: $paymentParameters ?? ValidMembershipApplication::newPaymentParameters(),
			trackingInfo: $this->getTrackingInfo(),
			applicantDateOfBirth: $applicantDateOfBirth,
			applicantPhoneNumber: $applicantPhoneNumber,
		);
	}

	private function newCompanyRequest( string $applicantCompanyName = 'ACME' ): ApplyForMembershipRequest {
		return ApplyForMembershipRequest::newCompanyApplyForMembershipRequest(
			membershipType: ValidMembershipApplication::MEMBERSHIP_TYPE,
			applicantCompanyName: $applicantCompanyName,
			applicantStreetAddress: ValidMembershipApplication::APPLICANT_STREET_ADDRESS,
			applicantPostalCode: ValidMembershipApplication::APPLICANT_POSTAL_CODE,
			applicantCity: ValidMembershipApplication::APPLICANT_CITY,
			applicantCountryCode: ValidMembershipApplication::APPLICANT_COUNTRY_CODE,
			applicantEmailAddress: ValidMembershipApplication::APPLICANT_EMAIL_ADDRESS,
			optsIntoDonationReceipt: true,
			incentives: [],
			paymentParameters: ValidMembershipApplication::newPaymentParameters(),
			trackingInfo: $this->getTrackingInfo()
		);
	}

	/**
	 * @param ApplyForMembershipRequest $request
	 * @param array<string, string> $expectedErrors
	 *
	 * @return void
	 */
	private function assertRequestValidationResultInErrors( ApplyForMembershipRequest $request, array $expectedErrors ): void {
		$this->assertEquals(
			new Result( $expectedErrors ),
			$this->newValidator()->validate( $request )
		);
	}

	public function testWhenDateOfBirthIsNotDate_validationFails(): void {
		$request = $this->newPrivateRequest( applicantDateOfBirth: 'this is not a valid date' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_DATE_OF_BIRTH => Result::VIOLATION_NOT_DATE ]
		);
	}

	public function testWhenApplicantPhoneNumberIsInvalid_validationFails(): void {
		$request = $this->newPrivateRequest( applicantPhoneNumber: 'potato' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_PHONE_NUMBER => Result::VIOLATION_NOT_PHONE_NUMBER ]
		);
	}

	/**
	 * @dataProvider emailViolationTypeProvider
	 */
	public function testWhenApplicantEmailIsInvalid_validationFails( string $emailViolationType ): void {
		$this->emailValidator = $this->newFailingEmailValidator( $emailViolationType );

		$request = $this->newPrivateRequest( applicantEmailAddress: 'this is not a valid email' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_EMAIL => Result::VIOLATION_NOT_EMAIL ]
		);
	}

	/**
	 * @return array<array<int, string>>
	 */
	public static function emailViolationTypeProvider(): array {
		return [
			[ 'email_address_wrong_format' ],
			[ 'email_address_invalid' ],
			[ 'email_address_domain_record_not_found' ],
		];
	}

	private function newFailingEmailValidator( string $violationType ): EmailValidator {
		$feeValidator = $this->createMock( EmailValidator::class );
		$feeValidator->method( 'validate' )
			->willReturn(
				new ValidationResult( new ConstraintViolation( 'this is not a valid email', $violationType ) )
			);
		return $feeValidator;
	}

	public function testWhenCompanyIsMissingFromCompanyApplication_validationFails(): void {
		$request = $this->newCompanyRequest( '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_COMPANY => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenFirstNameIsMissingFromPersonalApplication_validationFails(): void {
		$request = $this->newPrivateRequest( applicantFirstName: '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_FIRST_NAME => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenLastNameIsMissingFromPersonalApplication_validationFails(): void {
		$request = $this->newPrivateRequest( applicantLastName: '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_LAST_NAME => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenSalutationIsMissingFromPersonalApplication_validationFails(): void {
		$request = $this->newPrivateRequest( applicantSalutation: '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_SALUTATION => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenStreetAddressIsMissing_validationFails(): void {
		$request = $this->newPrivateRequest( applicantStreetAddress: '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_STREET_ADDRESS => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenPostalCodeIsMissing_validationFails(): void {
		$request = $this->newPrivateRequest( applicantPostalCode: '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_POSTAL_CODE => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenCityIsMissing_validationFails(): void {
		$request = $this->newPrivateRequest( applicantCity: '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_CITY => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenCountryCodeIsMissing_validationFails(): void {
		$request = $this->newPrivateRequest( applicantCountryCode: '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_COUNTRY => Result::VIOLATION_MISSING ]
		);
	}

	public function testWhenMembershipTypeIsMissing_validationFails(): void {
		$request = $this->newPrivateRequest( membershipType: '' );

		$this->assertRequestValidationResultInErrors(
			$request,
			[ Result::SOURCE_APPLICANT_MEMBERSHIP_TYPE => Result::VIOLATION_INVALID_MEMBERSHIP_TYPE ]
		);
	}

	public function testPhoneNumberIsNotProvided_validationDoesNotFail(): void {
		$request = $this->newPrivateRequest( applicantPhoneNumber: '' );

		$this->assertTrue( $this->newValidator()->validate( $request )->isSuccessful() );
	}

	public function testDateOfBirthIsNotProvided_validationDoesNotFail(): void {
		$request = $this->newPrivateRequest( applicantDateOfBirth: '' );

		$this->assertTrue( $this->newValidator()->validate( $request )->isSuccessful() );
	}

	public function testPersonalInfoWithLongFields_validationFails(): void {
		$longText = str_repeat( 'Cats ', 500 );
		$request = $this->newPrivateRequest(
			applicantSalutation: $longText,
			applicantTitle: $longText,
			applicantFirstName: $longText,
			applicantLastName: $longText,
			applicantStreetAddress: $longText,
			applicantPostalCode: $longText,
			applicantCity: $longText,
			applicantCountryCode: $longText
		);

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
		$request = $this->newPrivateRequest(
			applicantEmailAddress: str_repeat( 'Cats', 500 ) . '@example.com',
			applicantPhoneNumber:  str_repeat( '1234', 500 ),
		);

		$this->assertRequestValidationResultInErrors(
			$request,
			[
				Result::SOURCE_APPLICANT_EMAIL => Result::VIOLATION_WRONG_LENGTH,
				Result::SOURCE_APPLICANT_PHONE_NUMBER => Result::VIOLATION_WRONG_LENGTH
			]
		);
	}

	public function testGivenValidRequestUsingPayPal_validationSucceeds(): void {
		$validRequest = $this->newValidRequestUsingPayPal();
		$response = $this->newValidator()->validate( $validRequest );

		$this->assertEquals( new Result(), $response );
		$this->assertCount( 0, $response->getViolationSources() );
		$this->assertTrue( $response->isSuccessful() );
	}

	private function newValidRequestUsingPayPal(): ApplyForMembershipRequest {
		$paymentRequest = new PaymentParameters(
			ValidMembershipApplication::PAYMENT_AMOUNT_IN_EURO,
			ValidMembershipApplication::PAYMENT_PERIOD_IN_MONTHS->value,
			PaymentType::Paypal->value
		);
		return $this->newPrivateRequest( paymentParameters: $paymentRequest );
	}

	private function getTrackingInfo(): MembershipApplicationTrackingInfo {
		return new MembershipApplicationTrackingInfo(
			ValidMembershipApplication::TEMPLATE_CAMPAIGN,
			ValidMembershipApplication::TEMPLATE_NAME
		);
	}

}
