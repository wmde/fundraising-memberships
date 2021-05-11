<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication;

use WMDE\Fundraising\MembershipContext\UseCases\SimpleResponse;

/**
 * @license GPL-2.0-or-later
 */
class CancellationResponse implements SimpleResponse {

	private int $applicationId;
	private bool $success;

	private function __construct( int $applicationId, bool $isSuccess ) {
		$this->applicationId = $applicationId;
		$this->success = $isSuccess;
	}

	public function getMembershipApplicationId(): int {
		return $this->applicationId;
	}

	public function isSuccess(): bool {
		return $this->success;
	}

	public static function newSuccessResponse( int $membershipApplicationId ): self {
		return new self( $membershipApplicationId, true );
	}

	public static function newFailureResponse( int $membershipApplicationId ): self {
		return new self( $membershipApplicationId, false );
	}

}
