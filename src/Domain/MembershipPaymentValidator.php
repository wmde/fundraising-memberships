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
	public const FEE_TOO_HIGH = 'error_too_high';

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

	private const MAX_YEARLY_PAYMENT_IN_EURO = 100_000;
	private const MONTHS_PER_YEAR = 12;

	private Euro $membershipFee;
	private PaymentInterval $paymentIntervalInMonths;

	/**
	 * @param ApplicantType $applicantType
	 * @param PaymentType[] $allowedPaymentTypes
	 */
	public function __construct(
		private ApplicantType $applicantType,
		private array $allowedPaymentTypes
	) {
	}

	public function validatePaymentData( Euro $amount, PaymentInterval $interval, PaymentType $paymentType ): ValidationResponse {
		$this->membershipFee = $amount;
		$this->paymentIntervalInMonths = $interval;

		$errors = [];

		if ( $this->isInvalidPaymentIntervalForMemberships( $this->paymentIntervalInMonths ) ) {
			$errors[] = new ConstraintViolation( $interval, self::INVALID_INTERVAL, self::SOURCE_INTERVAL );
		} elseif ( $this->getYearlyPaymentAmount() < $this->getYearlyPaymentRequirement() ) {
			$errors[] = new ConstraintViolation( $interval, self::FEE_TOO_LOW, self::SOURCE_MEMBERSHIP_FEE );
		} elseif ( $this->getYearlyPaymentAmount() > self::MAX_YEARLY_PAYMENT_IN_EURO ) {
			$errors[] = new ConstraintViolation( $interval, self::FEE_TOO_HIGH, self::SOURCE_MEMBERSHIP_FEE );
		}

		if ( $this->isInvalidPaymentTypeForMemberships( $paymentType ) ) {
			$errors[] = new ConstraintViolation( $interval, self::INVALID_PAYMENT_TYPE, self::SOURCE_PAYMENT_TYPE );
		}

		return new ValidationResponse( $errors );
	}

	private function getYearlyPaymentAmount(): float {
		if ( $this->paymentIntervalInMonths->value !== 0 ) {
			return $this->membershipFee->getEuros() * self::MONTHS_PER_YEAR / $this->paymentIntervalInMonths->value;
		}
		throw new \LogicException( "Payment Interval should never be 0 here because it's not allowed for memberships." );
	}

	private function getYearlyPaymentRequirement(): float {
		return match ( $this->applicantType ) {
			ApplicantType::COMPANY_APPLICANT => self::MIN_COMPANY_YEARLY_PAYMENT_IN_EURO,
			ApplicantType::PERSON_APPLICANT => self::MIN_PERSON_YEARLY_PAYMENT_IN_EURO,
		};
	}

	private function isInvalidPaymentIntervalForMemberships( PaymentInterval $interval ): bool {
		return $interval === PaymentInterval::OneTime;
	}

	private function isInvalidPaymentTypeForMemberships( PaymentType $paymentType ): bool {
		return !in_array( $paymentType, $this->allowedPaymentTypes );
	}
}
