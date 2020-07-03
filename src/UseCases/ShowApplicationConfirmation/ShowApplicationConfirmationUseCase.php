<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ShowApplicationConfirmation;

use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationTokenFetcher;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationAnonymizedException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class ShowApplicationConfirmationUseCase {

	private $presenter;
	private $authorizer;
	private $repository;
	private $tokenFetcher;

	public function __construct( ShowApplicationConfirmationPresenter $presenter, ApplicationAuthorizer $authorizer,
		ApplicationRepository $repository, ApplicationTokenFetcher $tokenFetcher ) {
		$this->presenter = $presenter;
		$this->authorizer = $authorizer;
		$this->repository = $repository;
		$this->tokenFetcher = $tokenFetcher;
	}

	public function showConfirmation( ShowAppConfirmationRequest $request ): void {
		if ( !$this->authorizer->canAccessApplication( $request->getApplicationId() ) ) {
			$this->presenter->presentAccessViolation();
			return;
		}

		try {
			$application = $this->repository->getApplicationById( $request->getApplicationId() );
		}
		catch ( ApplicationAnonymizedException $ex ) {
			$this->presenter->presentApplicationWasAnonymized();
			return;
		}
		catch ( GetMembershipApplicationException $ex ) {
			$this->presenter->presentTechnicalError( 'A database error occurred' );
			return;
		}

		$this->presenter->presentConfirmation(
		// TODO: use DTO instead of Entity (currently violates the architecture)
			$application,
			$this->tokenFetcher->getTokens( $request->getApplicationId() )->getUpdateToken()
		);
	}

}
