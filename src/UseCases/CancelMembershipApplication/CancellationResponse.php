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

	public const IS_SUCCESS = true;
	public const IS_FAILURE = false;

	public function __construct( int $applicationId, bool $isSuccess ) {
		$this->applicationId = $applicationId;
		$this->success = $isSuccess;
	}

	public function getMembershipApplicationId(): int {
		return $this->applicationId;
	}

	public function isSuccess(): bool {
		return $this->success;
	}

}
