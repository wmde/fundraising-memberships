<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee;

/**
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ValidateFeeRequest {

	public const PERSON_APPLICANT = 'person';
	public const COMPANY_APPLICANT = 'firma';

	private $membershipFee;
	private $paymentIntervalInMonths;
	private $applicantType;

	public static function newInstance(): self {
		return new self();
	}

	public function withFee( string $membershipFee ): self {
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
		$request->applicantType = $applicantType;
		return $request;
	}

	public function getMembershipFee(): string {
		return $this->membershipFee;
	}

	public function getPaymentIntervalInMonths(): int {
		return $this->paymentIntervalInMonths;
	}

	public function getApplicantType(): string {
		return $this->applicantType;
	}

}