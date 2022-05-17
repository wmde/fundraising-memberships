<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ApplyForMembership;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicantType;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\MembershipPaymentValidator;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\PaymentType;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\MembershipPaymentValidator
 */
class MembershipPaymentValidatorTest extends TestCase {

	public const VALID_MIN_AMOUNT_FOR_COMPANY = 100;
	public const VALID_MIN_AMOUNT_FOR_PRIVATE_PERSON = 24;

	/**
	 * @dataProvider companyAmountProvider
	 */
	public function testGivenValidFeeAmountForCompany_validatorReturnsNoViolations( bool $isValid, int $amount ): void {
		$validator = new MembershipPaymentValidator( ApplicantType::COMPANY_APPLICANT );
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
		$validator = new MembershipPaymentValidator( ApplicantType::PERSON_APPLICANT );
		$response = $validator->validatePaymentData(
			Euro::newFromInt( $amount ),
			PaymentInterval::Yearly,
			ValidMembershipApplication::PAYMENT_TYPE_DIRECT_DEBIT
		);
		$this->assertEquals( $isValid, $response->isSuccessful() );
	}

	public function companyAmountProvider(): iterable {
		yield [ true, self::VALID_MIN_AMOUNT_FOR_COMPANY ];
		yield [ false, self::VALID_MIN_AMOUNT_FOR_COMPANY - 1 ];
		yield [ true, self::VALID_MIN_AMOUNT_FOR_COMPANY + 1 ];
	}

	public function privatePersonAmountProvider(): iterable {
		yield [ true, self::VALID_MIN_AMOUNT_FOR_PRIVATE_PERSON ];
		yield [ false, self::VALID_MIN_AMOUNT_FOR_PRIVATE_PERSON - 1 ];
		yield [ true, self::VALID_MIN_AMOUNT_FOR_PRIVATE_PERSON + 1 ];
	}

	/**
	 * @dataProvider tooLowAmountProvider
	 */
	public function testGivenFeeAmountTooLowPerYear_validatorReturnsErrors( ApplicantType $applicantType, int $lowAmount ): void {
		$validator = new MembershipPaymentValidator( $applicantType );
		$response = $validator->validatePaymentData(
			Euro::newFromInt( $lowAmount ),
			PaymentInterval::Quarterly,
			ValidMembershipApplication::PAYMENT_TYPE_DIRECT_DEBIT
		);
		$violations = $response->getValidationErrors();
		$this->assertCount( 1, $violations );
		$feeViolation = $violations[0];
		$this->assertEquals( 'fee_too_low', $feeViolation->getMessageIdentifier() );
	}

	public function tooLowAmountProvider(): iterable {
		yield [ ApplicantType::PERSON_APPLICANT, 5 ];
		yield [ ApplicantType::PERSON_APPLICANT, 4 ];
		yield [ ApplicantType::PERSON_APPLICANT, 0 ];
		yield [ ApplicantType::COMPANY_APPLICANT, 24 ];
		yield [ ApplicantType::COMPANY_APPLICANT, 23 ];
		yield [ ApplicantType::COMPANY_APPLICANT, 0 ];
	}

	public function testInvalidIntervalForMemberships_validatorReturnsErrors(): void {
		$validator = new MembershipPaymentValidator( ApplicantType::COMPANY_APPLICANT );
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
		$validator = new MembershipPaymentValidator( ApplicantType::COMPANY_APPLICANT );
		$response = $validator->validatePaymentData(
			Euro::newFromInt( 100 ),
			$validInterval,
			ValidMembershipApplication::PAYMENT_TYPE_DIRECT_DEBIT
		);
		$this->assertTrue( $response->isSuccessful() );
	}

	public function validIntervalProvider(): iterable {
		yield [ PaymentInterval::Quarterly ];
		yield [ PaymentInterval::HalfYearly ];
		yield [ PaymentInterval::Monthly ];
		yield [ PaymentInterval::Yearly ];
	}

	/**
	 * @dataProvider invalidPaymentTypeProvider
	 */
	public function testInvalidPaymentTypesForMemberships_validatorReturnsErrors( PaymentType $invalidPaymentType ): void {
		$validator = new MembershipPaymentValidator( ApplicantType::PERSON_APPLICANT );
		$response = $validator->validatePaymentData(
			Euro::newFromInt( ValidMembershipApplication::PAYMENT_AMOUNT_IN_EURO ),
			ValidMembershipApplication::PAYMENT_PERIOD_IN_MONTHS,
			$invalidPaymentType
		);
		$this->assertFalse( $response->isSuccessful() );
	}

	public function invalidPaymentTypeProvider(): iterable {
		yield [ PaymentType::BankTransfer ];
		yield [ PaymentType::Sofort ];
		yield [ PaymentType::CreditCard ];
	}

}
