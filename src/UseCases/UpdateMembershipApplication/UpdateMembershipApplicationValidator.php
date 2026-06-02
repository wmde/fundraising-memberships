<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\UpdateMembershipApplication;

use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicantType;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\Validators\AddressValidator;
use WMDE\FunValidators\Validators\EmailValidator;

class UpdateMembershipApplicationValidator {

	public function __construct(
		private readonly AddressValidator $addressValidator,
		private readonly EmailValidator $emailValidator
	) {
	}

	public function validateMembershipApplicationData( UpdateMembershipApplicationRequest $applicantRequest ): UpdateMembershipApplicationValidationResult {
		$applicantType = $applicantRequest->isCompanyApplication() ? ApplicantType::COMPANY_APPLICANT : ApplicantType::PERSON_APPLICANT;
		switch ( $applicantType ) {
			case ApplicantType::PERSON_APPLICANT:
				$nameViolations = $this->getPersonViolations( $applicantRequest );
				break;
			case ApplicantType::COMPANY_APPLICANT:
				$nameViolations = $this->getCompanyViolations( $applicantRequest );
				break;
			default:
				throw new \InvalidArgumentException( sprintf( ' Unknown applicant type: %s', $applicantType->name ) );
		}

		$violations = array_merge(
			$nameViolations,
			$this->getAddressViolations( $applicantRequest ),
			$this->getEmailViolations( $applicantRequest )
		);

		if ( $violations ) {
			return new UpdateMembershipApplicationValidationResult( ...$violations );
		}

		return new UpdateMembershipApplicationValidationResult();
	}

	/**
	 * @param UpdateMembershipApplicationRequest $applicantRequest
	 *
	 * @return ConstraintViolation[]
	 */
	private function getPersonViolations( UpdateMembershipApplicationRequest $applicantRequest ): array {
		return $this->addressValidator->validatePersonName(
				$applicantRequest->getSalutation(),
				$applicantRequest->getTitle(),
				$applicantRequest->getFirstName(),
				$applicantRequest->getLastName()
			)->getViolations();
	}

	/**
	 * @param UpdateMembershipApplicationRequest $applicantRequest
	 *
	 * @return ConstraintViolation[]
	 */
	private function getCompanyViolations( UpdateMembershipApplicationRequest $applicantRequest ): array {
		return $this->addressValidator->validateCompanyName(
				$applicantRequest->getCompanyName()
			)->getViolations();
	}

	/**
	 * @param UpdateMembershipApplicationRequest $applicantRequest
	 *
	 * @return ConstraintViolation[]
	 */
	private function getAddressViolations( UpdateMembershipApplicationRequest $applicantRequest ): array {
		return $this->addressValidator->validatePostalAddress(
				$applicantRequest->getStreetAddress(),
				$applicantRequest->getPostalCode(),
				$applicantRequest->getCity(),
				$applicantRequest->getCountryCode()
			)->getViolations();
	}

	/**
	 * @param UpdateMembershipApplicationRequest $applicantRequest
	 *
	 * @return ConstraintViolation[]
	 */
	private function getEmailViolations( UpdateMembershipApplicationRequest $applicantRequest ): array {
		return $this->emailValidator->validate( $applicantRequest->getEmailAddress()->getFullAddress() )->getViolations();
	}
}
