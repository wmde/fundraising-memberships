<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ModerateMembershipApplication;

use WMDE\Fundraising\MembershipContext\UseCases\SimpleResponse;

class ModerateMembershipApplicationResponse implements SimpleResponse {
	private int $membershipApplicationId;
	private bool $success;

	private function __construct( int $membershipApplicationId, bool $isSuccess ) {
		$this->membershipApplicationId = $membershipApplicationId;
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
