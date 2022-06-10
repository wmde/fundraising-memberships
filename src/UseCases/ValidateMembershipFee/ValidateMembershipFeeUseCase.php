<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ValidateMembershipFee;

use WMDE\Euro\Euro;
use WMDE\Fundraising\MembershipContext\Domain\MembershipPaymentValidator;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicantType;
use WMDE\Fundraising\PaymentContext\Domain\PaymentValidator;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\ValidationResponse;

class ValidateMembershipFeeUseCase {

	public const SOURCE_APPLICANT_TYPE = 'applicant-type';
	public const INVALID_APPLICANT_TYPE = 'invalid-applicant-type';

	public function validate( int $membershipFeeInEuro, int $paymentInterval, string $applicantTypeName, string $paymentType ): ValidationResponse {
		$applicantType = ApplicantType::tryFrom( $applicantTypeName );
		$membershipFeeInEuroCents = Euro::newFromInt( $membershipFeeInEuro )->getEuroCents();

		if ( $applicantType === null ) {
			return ValidationResponse::newFailureResponse(
				[ new ConstraintViolation( $applicantType, self::INVALID_APPLICANT_TYPE, self::SOURCE_APPLICANT_TYPE ) ]
			);
		}

		$domainSpecificValidator = new MembershipPaymentValidator( $applicantType );
		$validator = new PaymentValidator();
		return $validator->validatePaymentData( $membershipFeeInEuroCents, $paymentInterval, $paymentType, $domainSpecificValidator );
	}

}
