<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee;

use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\Domain\MembershipPaymentValidator;
use WMDE\Fundraising\MembershipContext\Infrastructure\PaymentServiceFactory;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicantType;
use WMDE\Fundraising\PaymentContext\Domain\PaymentValidator;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResponse;

class ValidateMembershipFeeUseCase {

	public const SOURCE_APPLICANT_TYPE = 'applicant-type';
	public const INVALID_APPLICANT_TYPE = 'invalid-applicant-type';

	public function __construct( private PaymentServiceFactory $paymentServiceFactory ) {
	}

	public function validate( int $membershipFeeInEuro, int $paymentInterval, string $applicantTypeName, string $paymentType ): ValidationResponse {
		try {
			$membershipFeeInEuroCents = Euro::newFromInt( $membershipFeeInEuro )->getEuroCents();
		} catch ( \InvalidArgumentException $e ) {
			return $this->handleExceptionFromEuroCreation( $membershipFeeInEuro, $e );
		}

		$applicantType = ApplicantType::tryFrom( $applicantTypeName );
		if ( $applicantType === null ) {
			return ValidationResponse::newFailureResponse(
				[ new ConstraintViolation( $applicantType, self::INVALID_APPLICANT_TYPE, self::SOURCE_APPLICANT_TYPE ) ]
			);
		}

		$domainSpecificValidator = $this->paymentServiceFactory->newPaymentValidator( $applicantType );
		$validator = new PaymentValidator();
		return $validator->validatePaymentData( $membershipFeeInEuroCents, $paymentInterval, $paymentType, $domainSpecificValidator );
	}

	private function handleExceptionFromEuroCreation( int $membershipFeeInEuro, \InvalidArgumentException $e ): ValidationResponse {
		// TODO We should modify the Euro class to throw a more specific exception, with numeric code
		//      (as a constant in the exception class) instead of messages
		if ( $e->getMessage() === 'Number is too big' ) {
			return ValidationResponse::newFailureResponse(
				[ new ConstraintViolation(
					$membershipFeeInEuro,
					MembershipPaymentValidator::FEE_TOO_HIGH,
					MembershipPaymentValidator::SOURCE_MEMBERSHIP_FEE
				) ]
			);
		} else {
			// With the current implementation of the Euro class, this should never happen.
			return ValidationResponse::newFailureResponse(
				[ new ConstraintViolation(
					$membershipFeeInEuro,
					$e->getMessage(),
					MembershipPaymentValidator::SOURCE_MEMBERSHIP_FEE
				) ]
			);
		}
	}

}
