<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ApplyForMembership;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\Domain\MembershipPaymentValidator;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicantType;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\PaymentType;

/**
 * @covers \WMDE\Fundraising\MembershipContext\Domain\MembershipPaymentValidator
 */
class MembershipPaymentValidatorTest extends TestCase {

	public const VALID_MIN_AMOUNT_FOR_COMPANY = 100;
	public const VALID_MIN_AMOUNT_FOR_PRIVATE_PERSON = 24;

	public const ALLOWED_PAYMENT_TYPES = [
		PaymentType::DirectDebit
	];

	/**
	 * @dataProvider companyAmountProvider
	 */
	public function testGivenValidFeeAmountForCompany_validatorReturnsNoViolations( bool $isValid, int $amount ): void {
		$validator = new MembershipPaymentValidator( ApplicantType::COMPANY_APPLICANT, self::ALLOWED_PAYMENT_TYPES );
		$response = $validator->validatePaymentData(
			Euro::newFromInt( $amount ),
			PaymentInterval::Yearly,
			ValidMembershipApplication::PAYMENT_TYPE_DIRECT_DEBIT
		);
		$this->assertEquals( $isValid, $response->isSuccessful() );
	}

	/**
	 * @dataProvider privatePersonAmountProvider
	 */
	public function testGivenValidFeeAmountForPrivatePerson_validatorReturnsNoViolations( bool $isValid, int $amount ): void {
		$validator = new MembershipPaymentValidator( ApplicantType::PERSON_APPLICANT, self::ALLOWED_PAYMENT_TYPES );
		$response = $validator->validatePaymentData(
			Euro::newFromInt( $amount ),
			PaymentInterval::Yearly,
			ValidMembershipApplication::PAYMENT_TYPE_DIRECT_DEBIT
		);
		$this->assertEquals( $isValid, $response->isSuccessful() );
	}

	public function testGivenTooHighFeeAmount_validatorReturnsViolation(): void {
		$validator = new MembershipPaymentValidator( ApplicantType::PERSON_APPLICANT, self::ALLOWED_PAYMENT_TYPES );
		$hugeAmount = 100_001;

		$response = $validator->validatePaymentData(
			Euro::newFromInt( $hugeAmount ),
			PaymentInterval::Yearly,
			ValidMembershipApplication::PAYMENT_TYPE_DIRECT_DEBIT
		);
		$violations = $response->getValidationErrors();
		$this->assertCount( 1, $violations );
		$feeViolation = $violations[0];
		$this->assertEquals( MembershipPaymentValidator::FEE_TOO_HIGH, $feeViolation->getMessageIdentifier() );
	}

	public static function companyAmountProvider(): iterable {
		yield [ true, self::VALID_MIN_AMOUNT_FOR_COMPANY ];
		yield [ false, self::VALID_MIN_AMOUNT_FOR_COMPANY - 1 ];
		yield [ true, self::VALID_MIN_AMOUNT_FOR_COMPANY + 1 ];
	}

	public static function privatePersonAmountProvider(): iterable {
		yield [ true, self::VALID_MIN_AMOUNT_FOR_PRIVATE_PERSON ];
		yield [ false, self::VALID_MIN_AMOUNT_FOR_PRIVATE_PERSON - 1 ];
		yield [ true, self::VALID_MIN_AMOUNT_FOR_PRIVATE_PERSON + 1 ];
	}

	/**
	 * @dataProvider tooLowAmountProvider
	 */
	public function testGivenFeeAmountTooLowPerYear_validatorReturnsErrors( ApplicantType $applicantType, int $lowAmount ): void {
		$validator = new MembershipPaymentValidator( $applicantType, self::ALLOWED_PAYMENT_TYPES );
		$response = $validator->validatePaymentData(
			Euro::newFromInt( $lowAmount ),
			PaymentInterval::Quarterly,
			ValidMembershipApplication::PAYMENT_TYPE_DIRECT_DEBIT
		);
		$violations = $response->getValidationErrors();
		$this->assertCount( 1, $violations );
		$feeViolation = $violations[0];
		$this->assertEquals( MembershipPaymentValidator::FEE_TOO_LOW, $feeViolation->getMessageIdentifier() );
	}

	public static function tooLowAmountProvider(): iterable {
		yield [ ApplicantType::PERSON_APPLICANT, 5 ];
		yield [ ApplicantType::PERSON_APPLICANT, 4 ];
		yield [ ApplicantType::PERSON_APPLICANT, 0 ];
		yield [ ApplicantType::COMPANY_APPLICANT, 24 ];
		yield [ ApplicantType::COMPANY_APPLICANT, 23 ];
		yield [ ApplicantType::COMPANY_APPLICANT, 0 ];
	}

	public function testInvalidIntervalForMemberships_validatorReturnsErrors(): void {
		$validator = new MembershipPaymentValidator( ApplicantType::COMPANY_APPLICANT, self::ALLOWED_PAYMENT_TYPES );
		$invalidInterval = PaymentInterval::OneTime;
		$response = $validator->validatePaymentData(
			 Euro::newFromInt( ValidMembershipApplication::PAYMENT_AMOUNT_IN_EURO ),
			$invalidInterval,
			ValidMembershipApplication::PAYMENT_TYPE_DIRECT_DEBIT
		);
		$this->assertFalse( $response->isSuccessful() );
	}

	/**
	 * @dataProvider validIntervalProvider
	 */
	public function testValidIntervalForMemberships_validatorReturnsNoErrors( PaymentInterval $validInterval ): void {
		$validator = new MembershipPaymentValidator( ApplicantType::COMPANY_APPLICANT, self::ALLOWED_PAYMENT_TYPES );
		$response = $validator->validatePaymentData(
			Euro::newFromInt( 100 ),
			$validInterval,
			ValidMembershipApplication::PAYMENT_TYPE_DIRECT_DEBIT
		);
		$this->assertTrue( $response->isSuccessful() );
	}

	public static function validIntervalProvider(): iterable {
		yield [ PaymentInterval::Quarterly ];
		yield [ PaymentInterval::HalfYearly ];
		yield [ PaymentInterval::Monthly ];
		yield [ PaymentInterval::Yearly ];
	}

	/**
	 * @dataProvider invalidPaymentTypeProvider
	 */
	public function testInvalidPaymentTypesForMemberships_validatorReturnsErrors( PaymentType $invalidPaymentType ): void {
		$validator = new MembershipPaymentValidator( ApplicantType::PERSON_APPLICANT, self::ALLOWED_PAYMENT_TYPES );
		$response = $validator->validatePaymentData(
			Euro::newFromInt( ValidMembershipApplication::PAYMENT_AMOUNT_IN_EURO ),
			ValidMembershipApplication::PAYMENT_PERIOD_IN_MONTHS,
			$invalidPaymentType
		);
		$this->assertFalse( $response->isSuccessful() );
	}

	public static function invalidPaymentTypeProvider(): iterable {
		yield [ PaymentType::BankTransfer ];
		yield [ PaymentType::Sofort ];
		yield [ PaymentType::CreditCard ];
		yield [ PaymentType::Paypal ];
	}

}
