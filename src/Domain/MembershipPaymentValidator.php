<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain;

use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicantType;
use WMDE\Fundraising\PaymentContext\Domain\DomainSpecificPaymentValidator;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\PaymentType;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResponse;

class MembershipPaymentValidator implements DomainSpecificPaymentValidator {
	/**
	 * Violation identifier for {@see ConstraintViolation}
	 */
	public const INVALID_INTERVAL = 'interval_invalid';

	/**
	 * Violation identifier for {@see ConstraintViolation}
	 */
	public const FEE_TOO_LOW = 'error_too_low';

	/**
	 * Violation identifier for {@see ConstraintViolation}
	 */
	public const INVALID_PAYMENT_TYPE = 'invalid_payment_type';

	/**
	 * Error source name for {@see ConstraintViolation}
	 */
	public const SOURCE_INTERVAL = 'interval';

	/**
	 * Error source name for {@see ConstraintViolation}
	 */
	public const SOURCE_MEMBERSHIP_FEE = 'fee';

	/**
	 * Error source name for {@see ConstraintViolation}
	 */
	public const SOURCE_PAYMENT_TYPE = 'payment_type';

	private const MIN_PERSON_YEARLY_PAYMENT_IN_EURO = 24;
	private const MIN_COMPANY_YEARLY_PAYMENT_IN_EURO = 100;
	private const MONTHS_PER_YEAR = 12;

	private Euro $membershipFee;
	private PaymentInterval $paymentIntervalInMonths;
	private ApplicantType $applicantType;
	private PaymentType $paymentType;

	public function __construct( ApplicantType $applicantType ) {
		$this->applicantType = $applicantType;
	}

	public function validatePaymentData( Euro $amount, PaymentInterval $interval, PaymentType $paymentType ): ValidationResponse {
		$this->membershipFee = $amount;
		$this->paymentIntervalInMonths = $interval;
		$this->paymentType = $paymentType;

		if ( $this->isInvalidPaymentIntervalForMemberships( $this->paymentIntervalInMonths ) ) {
			return ValidationResponse::newFailureResponse( [
				new ConstraintViolation( $interval, self::INVALID_INTERVAL, self::SOURCE_INTERVAL )
			] );
		}

		if ( $this->isInvalidPaymentTypeForMemberships( $this->paymentType ) ) {
			return ValidationResponse::newFailureResponse( [
				new ConstraintViolation( $interval, self::INVALID_PAYMENT_TYPE, self::SOURCE_PAYMENT_TYPE )
			] );
		}

		if ( $this->getYearlyPaymentAmount() < $this->getYearlyPaymentRequirement() ) {
			return ValidationResponse::newFailureResponse( [
				new ConstraintViolation( $interval, self::FEE_TOO_LOW, self::SOURCE_MEMBERSHIP_FEE )
			] );
		}

		return ValidationResponse::newSuccessResponse();
	}

	private function getYearlyPaymentAmount(): float {
		if ( $this->paymentIntervalInMonths->value !== 0 ) {
			return $this->membershipFee->getEuros() * self::MONTHS_PER_YEAR / $this->paymentIntervalInMonths->value;
		}
		throw new \LogicException( "Payment Interval should never be 0 here because it's not allowed for memberships." );
	}

	private function getYearlyPaymentRequirement(): float {
		return match( $this->applicantType ) {
			ApplicantType::COMPANY_APPLICANT => self::MIN_COMPANY_YEARLY_PAYMENT_IN_EURO,
			ApplicantType::PERSON_APPLICANT => self::MIN_PERSON_YEARLY_PAYMENT_IN_EURO,
			default => throw new \Exception( 'Unexpected applicant type' ),
		};
	}

	private function isInvalidPaymentIntervalForMemberships( PaymentInterval $interval ): bool {
		return $interval === PaymentInterval::OneTime;
	}

	private function isInvalidPaymentTypeForMemberships( PaymentType $paymentType ): bool {
		return $paymentType !== PaymentType::DirectDebit;
	}
}
