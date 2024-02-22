<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\Infrastructure;

use WMDE\Fundraising\MembershipContext\Domain\MembershipPaymentValidator;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicantType;
use WMDE\Fundraising\PaymentContext\Domain\PaymentType;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\CreatePaymentUseCase;

/**
 * This class is for passing down dependencies from a higher layer (Fundraising Application) to the use cases
 */
class PaymentServiceFactory {

	/**
	 * @param CreatePaymentUseCase $useCase
	 * @param PaymentType[] $allowedPaymentTypes
	 */
	public function __construct(
		private readonly CreatePaymentUseCase $useCase,
		private readonly array $allowedPaymentTypes ) {
	}

	public function getCreatePaymentUseCase(): CreatePaymentUseCase {
		return $this->useCase;
	}

	public function newPaymentValidator( ApplicantType $applicantType ): MembershipPaymentValidator {
		return new MembershipPaymentValidator( $applicantType, $this->allowedPaymentTypes );
	}
}
