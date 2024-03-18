<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication;

use WMDE\Fundraising\MembershipContext\UseCases\SimpleResponse;

/**
 * @todo Convert this into a value object with public read-only properties
 *    	 This is a backward breaking change, only do this if you are doing related backward breaking changes
 */
class CancellationResponse implements SimpleResponse {

	private bool $success;

	private function __construct( private readonly int $applicationId, bool $isSuccess ) {
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
