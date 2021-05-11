<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\RestoreMembershipApplication;

use WMDE\Fundraising\MembershipContext\UseCases\SimpleResponse;

class RestoreMembershipApplicationResponse implements SimpleResponse {

	public const SUCCESS = 'success';
	public const FAILURE = 'failure';

	private int $membershipApplicationId;
	private string $state;

	public function __construct( int $membershipApplicationId, string $state ) {
		$this->membershipApplicationId = $membershipApplicationId;
		$this->state = $state;
	}

	public function getMembershipApplicationId(): int {
		return $this->membershipApplicationId;
	}

	public function isSuccess(): bool {
		return $this->state !== self::FAILURE;
	}
}
