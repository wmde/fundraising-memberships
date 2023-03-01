<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ShowApplicationConfirmation;

use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationTokenFetcher;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationAnonymizedException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

class ShowApplicationConfirmationUseCase {

	public function __construct(
		private readonly ShowApplicationConfirmationPresenter $presenter,
		private readonly ApplicationAuthorizer $authorizer,
		private readonly ApplicationRepository $repository,
		private readonly ApplicationTokenFetcher $tokenFetcher,
		private readonly GetPaymentUseCase $getPaymentUseCase
	) {
	}

	public function showConfirmation( ShowAppConfirmationRequest $request ): void {
		if ( !$this->authorizer->canAccessApplication( $request->getApplicationId() ) ) {
			$this->presenter->presentAccessViolation();
			return;
		}

		try {
			$application = $this->repository->getApplicationById( $request->getApplicationId() );
			$paymentData = $this->getPaymentUseCase->getPaymentDataArray( $application->getPaymentId() );
		} catch ( ApplicationAnonymizedException $ex ) {
			$this->presenter->presentApplicationWasAnonymized();
			return;
		} catch ( GetMembershipApplicationException $ex ) {
			$this->presenter->presentTechnicalError( 'A database error occurred' );
			return;
		}

		$this->presenter->presentConfirmation(
		// TODO: use DTO instead of Entity (currently violates the architecture)
			$application,
			$paymentData,
			$this->tokenFetcher->getTokens( $request->getApplicationId() )->getUpdateToken()
		);
	}

}
