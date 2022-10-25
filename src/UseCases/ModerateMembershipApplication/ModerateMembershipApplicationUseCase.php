<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ModerateMembershipApplication;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationIdentifier;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Infrastructure\MembershipApplicationEventLogger;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;

class ModerateMembershipApplicationUseCase {

	public const LOG_MESSAGE_MARKED_FOR_MODERATION = 'marked for moderation by user: %s';
	public const LOG_MESSAGE_MARKED_AS_APPROVED = 'marked as approved by user: %s';

	public function __construct(
		private readonly ApplicationRepository $applicationRepository,
		private readonly MembershipApplicationEventLogger $applicationEventLogger,
		private readonly PaymentRepository $paymentRepository
	) {
	}

	public function markMembershipApplicationAsModerated( int $membershipApplicationId, string $authorizedUser ): ModerateMembershipApplicationResponse {
		$membershipApplication = $this->applicationRepository->getApplicationById( $membershipApplicationId );

		if ( $membershipApplication === null ) {
			return ModerateMembershipApplicationResponse::newFailureResponse( $membershipApplicationId );
		}

		if ( $membershipApplication->isMarkedForModeration() ) {
			return ModerateMembershipApplicationResponse::newFailureResponse( $membershipApplicationId );
		}

		if ( $membershipApplication->isCancelled() ) {
			return ModerateMembershipApplicationResponse::newFailureResponse( $membershipApplicationId );
		}

		$membershipApplication->markForModeration( new ModerationReason( ModerationIdentifier::MANUALLY_FLAGGED_BY_ADMIN ) );
		$this->applicationRepository->storeApplication( $membershipApplication );

		$this->applicationEventLogger->log(
			$membershipApplicationId,
			sprintf( self::LOG_MESSAGE_MARKED_FOR_MODERATION, $authorizedUser )
		);

		return ModerateMembershipApplicationResponse::newSuccessResponse( $membershipApplicationId );
	}

	public function approveMembershipApplication( int $membershipApplicationId, string $authorizedUser ): ModerateMembershipApplicationResponse {
		$membershipApplication = $this->applicationRepository->getApplicationById( $membershipApplicationId );

		if ( $membershipApplication === null ) {
			return ModerateMembershipApplicationResponse::newFailureResponse( $membershipApplicationId );
		}

		if ( !$membershipApplication->isMarkedForModeration() ) {
			return ModerateMembershipApplicationResponse::newFailureResponse( $membershipApplicationId );
		}

		$membershipApplication->approve();
		$this->confirmMembershipBasedOnPayment( $membershipApplication );

		$this->applicationRepository->storeApplication( $membershipApplication );

		$this->applicationEventLogger->log(
			$membershipApplicationId,
			sprintf( self::LOG_MESSAGE_MARKED_AS_APPROVED, $authorizedUser )
		);

		return ModerateMembershipApplicationResponse::newSuccessResponse( $membershipApplicationId );
	}

	private function confirmMembershipBasedOnPayment( MembershipApplication $membershipApplication ): void {
		$payment = $this->paymentRepository->getPaymentById( $membershipApplication->getPaymentId() );
		if ( $payment->isCompleted() ) {
			$membershipApplication->confirm();
		}
	}

}
