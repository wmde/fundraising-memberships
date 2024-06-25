<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\RestoreMembershipApplication;

use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipRepository;
use WMDE\Fundraising\MembershipContext\Infrastructure\MembershipApplicationEventLogger;
use WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\CancelPaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\FailureResponse;

class RestoreMembershipApplicationUseCase {

	public const LOG_MESSAGE_MARKED_AS_RESTORED = 'restored by user: %s';

	public function __construct(
		private readonly MembershipRepository $applicationRepository,
		private readonly MembershipApplicationEventLogger $applicationEventLogger,
		private readonly CancelPaymentUseCase $cancelPaymentUseCase
	) {
	}

	public function restoreApplication( int $membershipApplicationId, string $authorizedUser ): RestoreMembershipApplicationResponse {
		$membershipApplication = $this->applicationRepository->getUnexportedMembershipApplicationById( $membershipApplicationId );

		if ( $membershipApplication === null ) {
			return RestoreMembershipApplicationResponse::newFailureResponse( $membershipApplicationId );
		}

		if ( !$membershipApplication->isCancelled() ) {
			return RestoreMembershipApplicationResponse::newFailureResponse( $membershipApplicationId );
		}

		$cancelPaymentResponse = $this->cancelPaymentUseCase->restorePayment( $membershipApplication->getPaymentId() );

		if ( $cancelPaymentResponse instanceof FailureResponse ) {
			return RestoreMembershipApplicationResponse::newFailureResponse( $membershipApplicationId );
		}

		if ( $cancelPaymentResponse->paymentIsCompleted ) {
			$membershipApplication->confirm();
		}

		$membershipApplication->restore();
		$this->applicationRepository->storeApplication( $membershipApplication );

		$this->applicationEventLogger->log(
			$membershipApplicationId,
			sprintf( self::LOG_MESSAGE_MARKED_AS_RESTORED, $authorizedUser )
		);

		return RestoreMembershipApplicationResponse::newSuccessResponse( $membershipApplicationId );
	}
}
