<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\RestoreMembershipApplication;

use WMDE\Fundraising\MembershipContext\UseCases\SimpleResponse;

class RestoreMembershipApplicationResponse implements SimpleResponse {

	private bool $success;

	private function __construct( private readonly int $membershipApplicationId, bool $isSuccess ) {
		$this->success = $isSuccess;
	}

	public function getMembershipApplicationId(): int {
		return $this->membershipApplicationId;
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
