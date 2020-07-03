<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CancellationResponse {

	private $applicationId;
	private $isSuccess;

	public const IS_SUCCESS = true;
	public const IS_FAILURE = false;

	public function __construct( int $applicationId, bool $isSuccess ) {
		$this->applicationId = $applicationId;
		$this->isSuccess = $isSuccess;
	}

	public function getMembershipApplicationId(): int {
		return $this->applicationId;
	}

	public function cancellationWasSuccessful(): bool {
		return $this->isSuccess;
	}

}
