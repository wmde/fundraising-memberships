<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\RestoreMembershipApplication;

use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Infrastructure\MembershipApplicationEventLogger;

class RestoreMembershipApplicationUseCase {

	public const LOG_MESSAGE_MARKED_AS_RESTORED = 'restored by user: %s';

	private ApplicationRepository $applicationRepository;
	private MembershipApplicationEventLogger $applicationEventLogger;

	public function __construct( ApplicationRepository $applicationRepository, MembershipApplicationEventLogger $applicationEventLogger ) {
		$this->applicationRepository = $applicationRepository;
		$this->applicationEventLogger = $applicationEventLogger;
	}

	public function restoreApplication( int $membershipApplicationId, string $authorizedUser ): RestoreMembershipApplicationResponse {
		$membershipApplication = $this->applicationRepository->getApplicationById( $membershipApplicationId );

		if ( $membershipApplication === null ) {
			return RestoreMembershipApplicationResponse::newFailureResponse( $membershipApplicationId );
		}

		if ( !$membershipApplication->isCancelled() ) {
			return RestoreMembershipApplicationResponse::newFailureResponse( $membershipApplicationId );
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
