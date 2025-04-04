<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ShowApplicationConfirmation;

use WMDE\Fundraising\MembershipContext\Authorization\MembershipAuthorizationChecker;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipRepository;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipTrackingRepository;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

class ShowApplicationConfirmationUseCase {

	public function __construct(
		private readonly ShowApplicationConfirmationPresenter $presenter,
		private readonly MembershipAuthorizationChecker $authorizer,
		private readonly MembershipRepository $repository,
		private readonly GetPaymentUseCase $getPaymentUseCase,
		private readonly MembershipTrackingRepository $piwikTracker,
	) {
	}

	public function showConfirmation( ShowAppConfirmationRequest $request ): void {
		if ( !$this->authorizer->canAccessMembership( $request->getApplicationId() ) ) {
			$this->presenter->presentAccessViolation();
			return;
		}

		try {
			$application = $this->repository->getMembershipApplicationById( $request->getApplicationId() );
			$tracking = $this->piwikTracker->getTracking( $request->getApplicationId() );

			// This is here to make phpstan happy, the authorizer already checks for non-existing membership applications
			if ( $application === null ) {
				$this->presenter->presentTechnicalError( 'Membership application not found' );
				return;
			}

			$paymentData = $this->getPaymentUseCase->getPaymentDataArray( $application->getPaymentId() );
		} catch ( GetMembershipApplicationException $ex ) {
			$this->presenter->presentTechnicalError( 'A database error occurred' );
			return;
		}

		$this->presenter->presentConfirmation(
			// TODO: use DTO instead of Entity (currently violates the architecture)
			$application,
			$paymentData,
			$tracking,
		);
	}

}
