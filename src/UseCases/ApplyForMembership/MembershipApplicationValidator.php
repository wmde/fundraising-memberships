<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicationValidationResult as Result;
use WMDE\FunValidators\Validators\EmailValidator;

class MembershipApplicationValidator {

	private ApplyForMembershipRequest $request;

	/**
	 * @var string[] ApplicationValidationResult::SOURCE_ => ApplicationValidationResult::VIOLATION_
	 */
	private array $violations;

	/**
	 * @var array<string, int>
	 */
	private array $maximumFieldLengths = [
		Result::SOURCE_APPLICANT_PHONE_NUMBER => 30,
		Result::SOURCE_APPLICANT_EMAIL => 250,
		Result::SOURCE_APPLICANT_COMPANY => 100,
		Result::SOURCE_APPLICANT_FIRST_NAME => 50,
		Result::SOURCE_APPLICANT_LAST_NAME => 50,
		Result::SOURCE_APPLICANT_SALUTATION => 16,
		Result::SOURCE_APPLICANT_STREET_ADDRESS => 100,
		Result::SOURCE_APPLICANT_POSTAL_CODE => 8,
		Result::SOURCE_APPLICANT_CITY => 100,
		Result::SOURCE_APPLICANT_COUNTRY => 8,
	];

	public function __construct( private readonly EmailValidator $emailValidator ) {
	}

	public function validate( ApplyForMembershipRequest $applicationRequest ): Result {
		$this->request = $applicationRequest;
		$this->violations = [];

		$this->validateMembershipType();
		$this->validateApplicantName();
		$this->validateApplicantContactInfo();
		$this->validateApplicantDateOfBirth();
		$this->validateApplicantAddress();
		return new Result( $this->violations );
	}

	private function validateMembershipType(): void {
		$membershipType = $this->request->membershipType;
		if ( $membershipType !== MembershipApplication::ACTIVE_MEMBERSHIP && $membershipType !== MembershipApplication::SUSTAINING_MEMBERSHIP ) {
			$this->violations[Result::SOURCE_APPLICANT_MEMBERSHIP_TYPE] = Result::VIOLATION_INVALID_MEMBERSHIP_TYPE;
		}
	}

	private function validateApplicantDateOfBirth(): void {
		$dob = $this->request->applicantDateOfBirth;

		if ( $dob !== '' && !strtotime( $dob ) ) {
			$this->violations[Result::SOURCE_APPLICANT_DATE_OF_BIRTH] = Result::VIOLATION_NOT_DATE;
		}
	}

	private function validateApplicantContactInfo(): void {
		$this->validatePhoneNumber();

		$this->validateFieldLength( $this->request->applicantEmailAddress, Result::SOURCE_APPLICANT_EMAIL );
		if ( $this->emailValidator->validate( $this->request->applicantEmailAddress )->hasViolations() ) {
			$this->violations[Result::SOURCE_APPLICANT_EMAIL] = Result::VIOLATION_NOT_EMAIL;
		}
	}

	private function validatePhoneNumber(): void {
		$phoneNumber = $this->request->applicantPhoneNumber;

		$this->validateFieldLength( $phoneNumber, Result::SOURCE_APPLICANT_PHONE_NUMBER );
		if ( $phoneNumber !== '' && !preg_match( '/^[0-9\+\-\(\)]+/i', $phoneNumber ) ) {
			$this->violations[Result::SOURCE_APPLICANT_PHONE_NUMBER] = Result::VIOLATION_NOT_PHONE_NUMBER;
		}
	}

	private function validateApplicantName(): void {
		if ( $this->request->isCompanyApplication() ) {
			$this->validateCompanyName();
		} else {
			$this->validatePersonName();
		}
	}

	private function validateCompanyName(): void {
		if ( $this->request->applicantCompanyName === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_COMPANY] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength( $this->request->applicantCompanyName, Result::SOURCE_APPLICANT_COMPANY );
	}

	private function validatePersonName(): void {
		if ( $this->request->applicantFirstName === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_FIRST_NAME] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength( $this->request->applicantFirstName, Result::SOURCE_APPLICANT_FIRST_NAME );

		if ( $this->request->applicantLastName === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_LAST_NAME] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength( $this->request->applicantLastName, Result::SOURCE_APPLICANT_LAST_NAME );

		if ( $this->request->applicantSalutation === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_SALUTATION] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength( $this->request->applicantSalutation, Result::SOURCE_APPLICANT_SALUTATION );
	}

	private function validateApplicantAddress(): void {
		if ( $this->request->applicantStreetAddress === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_STREET_ADDRESS] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength(
			$this->request->applicantStreetAddress,
			Result::SOURCE_APPLICANT_STREET_ADDRESS
		);

		if ( $this->request->applicantPostalCode === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_POSTAL_CODE] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength( $this->request->applicantPostalCode, Result::SOURCE_APPLICANT_POSTAL_CODE );

		if ( $this->request->applicantCity === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_CITY] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength( $this->request->applicantCity, Result::SOURCE_APPLICANT_CITY );

		if ( $this->request->applicantCountryCode === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_COUNTRY] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength( $this->request->applicantCountryCode, Result::SOURCE_APPLICANT_COUNTRY );
	}

	private function validateFieldLength( string $value, string $fieldName ): void {
		if ( strlen( $value ) > $this->maximumFieldLengths[$fieldName] ) {
			$this->violations[$fieldName] = Result::VIOLATION_WRONG_LENGTH;
		}
	}

}
