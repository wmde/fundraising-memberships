<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

/**
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApplicationValidationResult {

	public const SOURCE_PAYMENT_AMOUNT = 'amount';
	public const SOURCE_APPLICANT_DATE_OF_BIRTH = 'applicant-dob';
	public const SOURCE_APPLICANT_PHONE_NUMBER = 'applicant-phone';
	public const SOURCE_APPLICANT_EMAIL = 'applicant-email';
	public const SOURCE_APPLICANT_COMPANY = 'company';
	public const SOURCE_APPLICANT_FIRST_NAME = 'applicant-first-name';
	public const SOURCE_APPLICANT_LAST_NAME = 'applicant-last-name';
	public const SOURCE_APPLICANT_SALUTATION = 'applicant-salutation';
	public const SOURCE_APPLICANT_STREET_ADDRESS = 'street-address';
	public const SOURCE_APPLICANT_POSTAL_CODE = 'postal-code';
	public const SOURCE_APPLICANT_CITY = 'city';
	public const SOURCE_APPLICANT_COUNTRY = 'country-code';
	public const SOURCE_APPLICANT_MEMBERSHIP_TYPE = 'membership-type';

	public const VIOLATION_TOO_LOW = 'too-low';
	public const VIOLATION_WRONG_LENGTH = 'wrong-length';
	public const VIOLATION_NOT_MONEY = 'not-money';
	public const VIOLATION_MISSING = 'missing';
	public const VIOLATION_IBAN_BLOCKED = 'iban-blocked';
	public const VIOLATION_NOT_DATE = 'not-date';
	public const VIOLATION_NOT_PHONE_NUMBER = 'not-phone';
	public const VIOLATION_NOT_EMAIL = 'not-email';
	public const VIOLATION_INVALID_MEMBERSHIP_TYPE = 'invalid-membership-type';

	private $violations;

	/**
	 * @param string[] $violations ApplicationValidationResult::SOURCE_ => ApplicationValidationResult::VIOLATION_
	 */
	public function __construct( array $violations = [] ) {
		$this->violations = $violations;
	}

	public function getViolations(): array {
		return $this->violations;
	}

	public function isSuccessful(): bool {
		return empty( $this->violations );
	}

	/**
	 * @return string[]
	 */
	public function getViolationSources(): array {
		return array_keys( $this->violations );
	}

	/**
	 * @param string $source
	 *
	 * @return string
	 * @throws \OutOfBoundsException
	 */
	public function getViolationType( string $source ): string {
		if ( array_key_exists( $source, $this->violations ) ) {
			 return $this->violations[$source];
		}

		throw new \OutOfBoundsException();
	}

}