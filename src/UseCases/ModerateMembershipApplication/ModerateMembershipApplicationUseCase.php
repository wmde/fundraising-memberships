<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ModerateMembershipApplication;

use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;

class ModerateMembershipApplicationUseCase {

	private ApplicationRepository $applicationRepository;

	public function __construct( ApplicationRepository $applicationRepository ) {
		$this->applicationRepository = $applicationRepository;
	}

	public function markMembershipApplicationAsModerated( int $membershipApplicationId, string $authorizedUser ): ModerateMembershipApplicationResponse {
		$membershipApplication = $this->applicationRepository->getApplicationById( $membershipApplicationId );

		if ( $membershipApplication === null ) {
			return $this->newModerationFailureResponse( $membershipApplicationId );
		}

		if ( $membershipApplication->needsModeration() ) {
			return $this->newModerationFailureResponse( $membershipApplicationId );
		}

		if ( $membershipApplication->isCancelled() ) {
			return $this->newModerationFailureResponse( $membershipApplicationId );
		}

		$membershipApplication->markForModeration();
		$this->applicationRepository->storeApplication( $membershipApplication );

		return $this->newModerationSuccessResponse( $membershipApplicationId );
	}

	public function approveMembershipApplication( int $membershipApplicationId, string $authorizedUser ): ModerateMembershipApplicationResponse {
		$membershipApplication = $this->applicationRepository->getApplicationById( $membershipApplicationId );

		if ( $membershipApplication === null ) {
			return $this->newModerationFailureResponse( $membershipApplicationId );
		}

		if ( !$membershipApplication->needsModeration() ) {
			return $this->newModerationFailureResponse( $membershipApplicationId );
		}

		$membershipApplication->approve();
		$this->applicationRepository->storeApplication( $membershipApplication );

		return $this->newModerationSuccessResponse( $membershipApplicationId );
	}

	private function newModerationFailureResponse( int $membershipApplicationId ): ModerateMembershipApplicationResponse {
		return new ModerateMembershipApplicationResponse(
			$membershipApplicationId,
			ModerateMembershipApplicationResponse::FAILURE
		);
	}

	private function newModerationSuccessResponse( int $membershipApplicationId ): ModerateMembershipApplicationResponse {
		return new ModerateMembershipApplicationResponse(
			$membershipApplicationId,
			ModerateMembershipApplicationResponse::SUCCESS
		);
	}
}
