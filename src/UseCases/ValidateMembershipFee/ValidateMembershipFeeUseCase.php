<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee;

use WMDE\Euro\Euro;

class ValidateMembershipFeeUseCase {

	private const MIN_PERSON_YEARLY_PAYMENT_IN_EURO = 24;
	private const MIN_COMPANY_YEARLY_PAYMENT_IN_EURO = 100;
	private const MONTHS_PER_YEAR = 12;

	public const APPLICANT_TYPE_COMPANY = 'firma';
	public const APPLICANT_TYPE_PERSON = 'person';

	private Euro $membershipFee;

	private int $paymentIntervalInMonths;
	private string $applicantType;

	public function validate( ValidateFeeRequest $request ): ValidateFeeResult {
		$this->membershipFee = $request->getMembershipFee();
		$this->paymentIntervalInMonths = $request->getPaymentIntervalInMonths();
		$this->applicantType = $request->getApplicantType();

		if ( $this->paymentIntervalInMonths < 1 ) {
			return ValidateFeeResult::newIntervalInvalidResponse();
		}

		if ( $this->getYearlyPaymentAmount() < $this->getYearlyPaymentRequirement() ) {
			return ValidateFeeResult::newTooLowResponse();
		}

		return ValidateFeeResult::newSuccessResponse();
	}

	private function getYearlyPaymentAmount(): float {
		return $this->membershipFee->getEuroFloat() * self::MONTHS_PER_YEAR / $this->paymentIntervalInMonths;
	}

	private function getYearlyPaymentRequirement(): float {
		return $this->applicantType === self::APPLICANT_TYPE_COMPANY ?
			self::MIN_COMPANY_YEARLY_PAYMENT_IN_EURO :
			self::MIN_PERSON_YEARLY_PAYMENT_IN_EURO;
	}

}
