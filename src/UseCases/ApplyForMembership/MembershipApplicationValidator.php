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

	public function __construct( private EmailValidator $emailValidator ) {
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
		$membershipType = $this->request->getMembershipType();
		if ( $membershipType !== MembershipApplication::ACTIVE_MEMBERSHIP && $membershipType !== MembershipApplication::SUSTAINING_MEMBERSHIP ) {
			$this->violations[Result::SOURCE_APPLICANT_MEMBERSHIP_TYPE] = Result::VIOLATION_INVALID_MEMBERSHIP_TYPE;
		}
	}

	private function validateApplicantDateOfBirth(): void {
		$dob = $this->request->getApplicantDateOfBirth();

		if ( $dob !== '' && !strtotime( $dob ) ) {
			$this->violations[Result::SOURCE_APPLICANT_DATE_OF_BIRTH] = Result::VIOLATION_NOT_DATE;
		}
	}

	private function validateApplicantContactInfo(): void {
		$this->validatePhoneNumber();

		$this->validateFieldLength( $this->request->getApplicantEmailAddress(), Result::SOURCE_APPLICANT_EMAIL );
		if ( $this->emailValidator->validate( $this->request->getApplicantEmailAddress() )->hasViolations() ) {
			$this->violations[Result::SOURCE_APPLICANT_EMAIL] = Result::VIOLATION_NOT_EMAIL;
		}
	}

	private function validatePhoneNumber(): void {
		$phoneNumber = $this->request->getApplicantPhoneNumber();

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
		if ( $this->request->getApplicantCompanyName() === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_COMPANY] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength( $this->request->getApplicantCompanyName(), Result::SOURCE_APPLICANT_COMPANY );
	}

	private function validatePersonName(): void {
		if ( $this->request->getApplicantFirstName() === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_FIRST_NAME] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength( $this->request->getApplicantFirstName(), Result::SOURCE_APPLICANT_FIRST_NAME );

		if ( $this->request->getApplicantLastName() === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_LAST_NAME] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength( $this->request->getApplicantLastName(), Result::SOURCE_APPLICANT_LAST_NAME );

		if ( $this->request->getApplicantSalutation() === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_SALUTATION] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength( $this->request->getApplicantSalutation(), Result::SOURCE_APPLICANT_SALUTATION );
	}

	private function validateApplicantAddress(): void {
		if ( $this->request->getApplicantStreetAddress() === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_STREET_ADDRESS] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength(
			$this->request->getApplicantStreetAddress(),
			Result::SOURCE_APPLICANT_STREET_ADDRESS
		);

		if ( $this->request->getApplicantPostalCode() === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_POSTAL_CODE] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength( $this->request->getApplicantPostalCode(), Result::SOURCE_APPLICANT_POSTAL_CODE );

		if ( $this->request->getApplicantCity() === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_CITY] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength( $this->request->getApplicantCity(), Result::SOURCE_APPLICANT_CITY );

		if ( $this->request->getApplicantCountryCode() === '' ) {
			$this->violations[Result::SOURCE_APPLICANT_COUNTRY] = Result::VIOLATION_MISSING;
		}
		$this->validateFieldLength( $this->request->getApplicantCountryCode(), Result::SOURCE_APPLICANT_COUNTRY );
	}

	private function validateFieldLength( string $value, string $fieldName ): void {
		if ( strlen( $value ) > $this->maximumFieldLengths[$fieldName] ) {
			$this->violations[$fieldName] = Result::VIOLATION_WRONG_LENGTH;
		}
	}

}
