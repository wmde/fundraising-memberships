<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication;

use WMDE\Fundraising\MembershipContext\Authorization\MembershipAuthorizationChecker;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipRepository;
use WMDE\Fundraising\MembershipContext\Infrastructure\MembershipApplicationEventLogger;
use WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\CancelPaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CancelPayment\FailureResponse;

class CancelMembershipApplicationUseCase {

	public const LOG_MESSAGE_FRONTEND_STATUS_CHANGE = 'frontend: cancellation';
	public const LOG_MESSAGE_ADMIN_STATUS_CHANGE = 'cancelled by user: %s';

	public function __construct(
		private readonly MembershipAuthorizationChecker $authorizer,
		private readonly MembershipRepository $repository,
		private readonly MembershipApplicationEventLogger $membershipApplicationEventLogger,
		private readonly CancelPaymentUseCase $cancelPaymentUseCase
	) {
	}

	public function cancelApplication( CancellationRequest $request ): CancellationResponse {
		$membershipApplicationId = $request->getApplicationId();
		if ( !$this->authorizer->canModifyMembership( $membershipApplicationId ) ) {
			return CancellationResponse::newFailureResponse( $membershipApplicationId );
		}

		$application = $this->getApplicationById( $membershipApplicationId );

		if ( $application === null ) {
			return CancellationResponse::newFailureResponse( $membershipApplicationId );
		}

		$cancelPaymentResponse = $this->cancelPaymentUseCase->cancelPayment( $application->getPaymentId() );

		if ( $cancelPaymentResponse instanceof FailureResponse ) {
			return CancellationResponse::newFailureResponse( $membershipApplicationId );
		}

		if ( !$application->isCancelled() ) {
			$application->cancel();
			$this->repository->storeApplication( $application );
		}

		$this->membershipApplicationEventLogger->log( $membershipApplicationId, $this->getLogMessage( $request ) );

		return CancellationResponse::newSuccessResponse( $membershipApplicationId );
	}

	public function getLogMessage( CancellationRequest $cancellationRequest ): string {
		if ( $cancellationRequest->initiatedByApplicant() ) {
			return sprintf( self::LOG_MESSAGE_ADMIN_STATUS_CHANGE, $cancellationRequest->getUserName() );
		}
		return self::LOG_MESSAGE_FRONTEND_STATUS_CHANGE;
	}

	private function getApplicationById( int $id ): ?MembershipApplication {
		try {
			return $this->repository->getUnexportedAndUnscrubbedMembershipApplicationById( $id );
		} catch ( GetMembershipApplicationException $ex ) {
			return null;
		}
	}
}
