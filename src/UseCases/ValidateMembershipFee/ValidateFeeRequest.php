<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee;

use WMDE\Euro\Euro;

class ValidateFeeRequest {

	public const PERSON_APPLICANT = 'person';
	public const COMPANY_APPLICANT = 'firma';

	private Euro $membershipFee;
	private int $paymentIntervalInMonths;
	private string $applicantType;

	public static function newInstance(): self {
		return new self();
	}

	public function withFee( Euro $membershipFee ): self {
		$request = clone $this;
		$request->membershipFee = $membershipFee;
		return $request;
	}

	public function withInterval( int $paymentIntervalInMonths ): self {
		$request = clone $this;
		$request->paymentIntervalInMonths = $paymentIntervalInMonths;
		return $request;
	}

	public function withApplicantType( string $applicantType ): self {
		$request = clone $this;
		$request->applicantType = trim( $applicantType );
		return $request;
	}

	public function getMembershipFee(): Euro {
		return $this->membershipFee;
	}

	public function getPaymentIntervalInMonths(): int {
		return $this->paymentIntervalInMonths;
	}

	public function getApplicantType(): string {
		return $this->applicantType;
	}

}
