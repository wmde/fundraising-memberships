<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\RestoreMembershipApplication;

class RestoreMembershipApplicationResponse {

	public const SUCCESS = 'success';
	public const FAILURE = 'failure';

	private int $membershipApplicationId;
	private string $state;

	public function __construct( int $membershipApplicationId, string $state ) {
		$this->membershipApplicationId = $membershipApplicationId;
		$this->state = $state;
	}

	public function getMembershipApplication(): int {
		return $this->membershipApplicationId;
	}

	public function restoreSucceeded(): bool {
		return $this->state !== self::FAILURE;
	}
}
