<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ModerateMembershipApplication;

class ModerateMembershipApplicationResponse {
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

	public function moderationChangeSucceeded(): bool {
		return $this->state !== self::FAILURE;
	}
}
