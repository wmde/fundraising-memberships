<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership;

use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicationValidationResult as Result;
use WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee\ValidateFeeRequest;
use WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee\ValidateFeeResult;
use WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee\ValidateMembershipFeeUseCase;
use WMDE\Fundraising\MembershipContext\Domain\Model\Application;
use WMDE\Fundraising\PaymentContext\Domain\BankDataValidationResult;
use WMDE\Fundraising\PaymentContext\Domain\BankDataValidator;
use WMDE\Fundraising\PaymentContext\Domain\IbanBlocklist;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentMethod;
use WMDE\FunValidators\Validators\EmailValidator;

/**
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MembershipApplicationValidator {

	private $feeValidator;
	private $bankDataValidator;
	private $ibanBlocklist;
	private $emailValidator;

	/**
	 * @var ApplyForMembershipRequest
	 */
	private $request;

	/**
	 * @var string[] ApplicationValidationResult::SOURCE_ => ApplicationValidationResult::VIOLATION_
	 */
	private $violations;

	private $maximumFieldLengths = [
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

	public function __construct( ValidateMembershipFeeUseCase $feeValidator, BankDataValidator $bankDataValidator,
		IbanBlocklist $ibanBlocklist, EmailValidator $emailValidator ) {

		$this->feeValidator = $feeValidator;
		$this->bankDataValidator = $bankDataValidator;
		$this->ibanBlocklist = $ibanBlocklist;
		$this->emailValidator = $emailValidator;
	}

	public function validate( ApplyForMembershipRequest $applicationRequest ): Result {
		$this->request = $applicationRequest;
		$this->violations = [];

		$this->validateMembershipType();
		$this->validateFee();
		$this->validateApplicantName();
		$this->validateApplicantContactInfo();
		$this->validateApplicantDateOfBirth();
		$this->validateApplicantAddress();
		if ( $applicationRequest->getPaymentType() === PaymentMethod::DIRECT_DEBIT ) {
			$this->validateBankData();
			$this->checkForBlockedIban();
		}

		return new Result( $this->violations );
	}

	private function validateMembershipType(): void {
		$membershiptype = $this->request->getMembershipType();
		if ( $membershiptype !== Application::ACTIVE_MEMBERSHIP && $membershiptype !== Application::SUSTAINING_MEMBERSHIP ) {
			$this->violations[Result::SOURCE_APPLICANT_MEMBERSHIP_TYPE] = Result::VIOLATION_INVALID_MEMBERSHIP_TYPE;
		}
	}

	private function validateFee(): void {
		$result = $this->feeValidator->validate(
			ValidateFeeRequest::newInstance()
				->withFee( $this->request->getPaymentAmountInEuros() )
				->withApplicantType( $this->getApplicantType() )
				->withInterval( $this->request->getPaymentIntervalInMonths() )
		);

		if ( !$result->isSuccessful() ) {
			$this->addPaymentAmountViolation(
				[
					ValidateFeeResult::ERROR_TOO_LOW => ApplicationValidationResult::VIOLATION_TOO_LOW
				][$result->getErrorCode()]
			);
		}
	}

	private function addPaymentAmountViolation( string $violation ) {
		$this->violations[ApplicationValidationResult::SOURCE_PAYMENT_AMOUNT] = $violation;
	}

	private function getApplicantType(): string {
		return $this->request->isCompanyApplication() ?
			ValidateMembershipFeeUseCase::APPLICANT_TYPE_COMPANY : ValidateMembershipFeeUseCase::APPLICANT_TYPE_PERSON;
	}

	private function addViolations( array $violations ): void {
		$this->violations = array_merge( $this->violations, $violations );
	}

	private function validateBankData(): void {
		$bankData = $this->request->getBankData();
		$validationResult = $this->bankDataValidator->validate( $bankData );
		$violations = [];

		foreach ( $validationResult->getViolations() as $violation ) {
			$violations[$violation->getSource()] = $violation->getMessageIdentifier();
		}

		$this->addViolations( $violations );
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
		}
		else {
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

	private function checkForBlockedIban() {
		if ( $this->ibanBlocklist->isIbanBlocked( $this->request->getBankData()->getIban() ) ) {
			$this->violations[BankDataValidationResult::SOURCE_IBAN] = Result::VIOLATION_IBAN_BLOCKED;
		}
	}

}