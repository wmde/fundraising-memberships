<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ModerateMembershipApplication;

use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Infrastructure\MembershipApplicationEventLogger;

class ModerateMembershipApplicationUseCase {

	public const LOG_MESSAGE_MARKED_FOR_MODERATION = 'marked for moderation by user: %s';
	public const LOG_MESSAGE_MARKED_AS_APPROVED = 'marked as approved by user: %s';

	private ApplicationRepository $applicationRepository;
	private MembershipApplicationEventLogger $applicationEventLogger;

	public function __construct( ApplicationRepository $applicationRepository, MembershipApplicationEventLogger $applicationEventLogger ) {
		$this->applicationRepository = $applicationRepository;
		$this->applicationEventLogger = $applicationEventLogger;
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

		$this->applicationEventLogger->log(
			$membershipApplicationId,
			sprintf( self::LOG_MESSAGE_MARKED_FOR_MODERATION, $authorizedUser )
		);

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

		$this->applicationEventLogger->log(
			$membershipApplicationId,
			sprintf( self::LOG_MESSAGE_MARKED_AS_APPROVED, $authorizedUser )
		);

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
